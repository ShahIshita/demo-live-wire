<?php

namespace App\Console\Commands;

use App\Support\LocalSubscriptionBilling;
use App\Support\StripeTimeouts;
use App\UserSubscription;
use Illuminate\Console\Command;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class ProcessSubscriptionRenewals extends Command
{
    protected $signature = 'subscriptions:renew
                            {--product-id= : Only renew subscriptions for this product ID}
                            {--subscription= : Only process this user_subscriptions.id}
                            {--dry-run : List due subscriptions without charging}';

    protected $description = 'Auto-renew due local subscriptions via off-session Stripe PaymentIntents';

    public function handle(): int
    {
        $secret = config('services.stripe.secret') ?: env('STRIPE_SECRET');
        if (empty($secret)) {
            $this->error('Stripe is not configured (STRIPE_SECRET).');

            return 1;
        }

        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->warn('Dry run: no charges will be made.');
        }

        Stripe::setApiKey($secret);
        StripeTimeouts::apply();

        $query = UserSubscription::query()
            ->whereIn('status', ['active', 'trialing', 'past_due', 'unpaid'])
            ->whereNotNull('next_billing_at')
            ->where('next_billing_at', '<=', now())
            ->with(['subscriptionPlan', 'user', 'product']);

        if ($this->option('subscription')) {
            $query->where('id', (int) $this->option('subscription'));
        }

        if ($this->option('product-id')) {
            $query->where('product_id', (int) $this->option('product-id'));
        }

        $due = $query->orderBy('id')->get();

        if ($due->isEmpty()) {
            $this->info('No subscriptions due for renewal.');

            return 0;
        }

        $this->info("Found {$due->count()} subscription(s) due for renewal.");

        foreach ($due as $sub) {
            if ($dryRun) {
                $label = $sub->product_name_snapshot
                    ?: ($sub->product->name ?? ('product #' . $sub->product_id));
                $this->line("- #{$sub->id} user {$sub->user_id} | {$label} | next_billing_at {$sub->next_billing_at}");

                continue;
            }

            $this->processOne($sub);
        }

        return 0;
    }

    protected function processOne(UserSubscription $sub): void
    {
        if ($sub->cancel_at_period_end) {
            if ($sub->next_billing_at && $sub->next_billing_at->lte(now())) {
                $sub->update(['status' => 'canceled']);
                $this->line("Canceled subscription local id {$sub->id} (end of period).");
            }

            return;
        }

        if ($sub->status === 'trialing' && $sub->trial_ends_at && $sub->trial_ends_at->isFuture()) {
            return;
        }

        $user = $sub->user;
        $plan = $sub->subscriptionPlan;
        if (!$user || !$plan || empty($user->stripe_customer_id) || empty($sub->stripe_default_payment_method_id)) {
            $this->warn("Skip subscription {$sub->id}: missing user, plan, customer, or payment method.");

            return;
        }

        try {
            $pi = PaymentIntent::create([
                'amount' => $plan->recurringAmountCents(),
                'currency' => $plan->stripeCurrency(),
                'customer' => $user->stripe_customer_id,
                'payment_method' => $sub->stripe_default_payment_method_id,
                'off_session' => true,
                'confirm' => true,
                'metadata' => [
                    'user_subscription_id' => (string) $sub->id,
                    'billing_type' => LocalSubscriptionBilling::BILLING_TYPE_RENEWAL,
                ],
            ]);

            if ($pi->status !== 'succeeded') {
                throw new \RuntimeException('Unexpected PaymentIntent status: ' . $pi->status);
            }

            $next = $plan->computeNextBillingAfter(now());
            $sub->update([
                'status' => 'active',
                'trial_ends_at' => null,
                'next_billing_at' => $next,
                'current_period_ends_at' => $next,
            ]);

            $productLabel = $sub->product_name_snapshot
                ?: ($sub->product->name ?? ('product #' . $sub->product_id));
            $this->info("Renewed subscription {$sub->id} ({$productLabel}); next billing {$next->toDateTimeString()}");
        } catch (ApiErrorException | \RuntimeException $e) {
            $sub->update(['status' => 'past_due']);
            $productLabel = $sub->product_name_snapshot
                ?: ($sub->product->name ?? ('product #' . $sub->product_id));
            $this->warn("Renewal failed for subscription {$sub->id} ({$productLabel}): {$e->getMessage()}");
        }
    }
}
