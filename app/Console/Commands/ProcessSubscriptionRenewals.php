<?php

namespace App\Console\Commands;

use App\Support\StripeTimeouts;
use App\UserSubscription;
use Illuminate\Console\Command;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

/**
 * Recurring charges without Stripe Subscription objects — only PaymentIntents appear in Stripe.
 */
class ProcessSubscriptionRenewals extends Command
{
    protected $signature = 'subscriptions:renew';

    protected $description = 'Charge due local subscriptions via off-session PaymentIntents';

    public function handle(): int
    {
        $secret = config('services.stripe.secret') ?: env('STRIPE_SECRET');
        if (empty($secret)) {
            $this->error('Stripe is not configured (STRIPE_SECRET).');

            return 1;
        }

        Stripe::setApiKey($secret);
        StripeTimeouts::apply();

        $due = UserSubscription::query()
            ->whereIn('status', ['active', 'trialing'])
            ->whereNotNull('next_billing_at')
            ->where('next_billing_at', '<=', now())
            ->with(['subscriptionPlan', 'user'])
            ->get();

        foreach ($due as $sub) {
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
                    'billing_type' => 'subscription_renewal',
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

            $this->info("Renewed subscription {$sub->id}; next billing {$next->toDateTimeString()}");
        } catch (ApiErrorException | \RuntimeException $e) {
            $sub->update(['status' => 'past_due']);
            $this->warn("Renewal failed for subscription {$sub->id}: {$e->getMessage()}");
        }
    }
}
