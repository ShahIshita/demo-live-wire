<?php

namespace App\Http\Controllers;

use App\Product;
use App\SubscriptionPlan;
use App\Support\LocalSubscriptionBilling;
use App\Support\StripeTimeouts;
use App\User;
use App\UserSubscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\SetupIntent;
use Stripe\Stripe;

/**
 * Subscriptions are stored in the database only. Stripe does not manage subscription objects or catalog products;
 * charges are one-off PaymentIntents / SetupIntents. Renewals run via {@see \App\Console\Commands\ProcessSubscriptionRenewals}.
 */
class SubscriptionController extends Controller
{
    protected function extendExecutionTime(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }
    }

    protected function ensureStripeKey(): void
    {
        $secret = config('services.stripe.secret') ?: env('STRIPE_SECRET');
        if (empty($secret)) {
            abort(500, 'Stripe not configured. Add STRIPE_SECRET to .env and run: php artisan config:clear');
        }

        Stripe::setApiKey($secret);
        StripeTimeouts::apply();
    }

    protected function resolveStripeCustomer(User $user): string
    {
        if (!empty($user->stripe_customer_id)) {
            return $user->stripe_customer_id;
        }

        $customer = \Stripe\Customer::create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        $user->stripe_customer_id = $customer->id;
        $user->save();

        return $customer->id;
    }

    protected function isStripeCurrencyConflict(ApiErrorException $e): bool
    {
        $msg = $e->getMessage();

        return stripos($msg, 'cannot combine currencies') !== false;
    }

    protected function recreateStripeCustomer(User $user): string
    {
        $user->stripe_customer_id = null;
        $user->save();

        return $this->resolveStripeCustomer($user->fresh());
    }

    protected function attachPaymentMethodToCustomer(string $paymentMethodId, string $customerId): void
    {
        $pm = PaymentMethod::retrieve($paymentMethodId);
        if (!empty($pm->customer) && $pm->customer === $customerId) {
            return;
        }
        if (!empty($pm->customer) && $pm->customer !== $customerId) {
            $pm->detach();
            $pm = PaymentMethod::retrieve($paymentMethodId);
        }
        try {
            $pm->attach(['customer' => $customerId]);
        } catch (ApiErrorException $e) {
            $pm = PaymentMethod::retrieve($paymentMethodId);
            if (empty($pm->customer) || $pm->customer !== $customerId) {
                throw $e;
            }
        }
    }

    protected function setCustomerDefaultPaymentMethod(string $customerId, string $paymentMethodId): void
    {
        \Stripe\Customer::update($customerId, [
            'invoice_settings' => [
                'default_payment_method' => $paymentMethodId,
            ],
        ]);
    }

    protected function hasRunningSubscriptionForPlan(User $user, int $subscriptionPlanId): bool
    {
        $running = UserSubscription::query()
            ->where('user_id', $user->id)
            ->where('subscription_plan_id', $subscriptionPlanId)
            ->whereIn('status', ['active', 'trialing', 'past_due', 'unpaid'])
            ->where('cancel_at_period_end', false)
            ->exists();

        return $running;
    }

    protected function firstChargeAmountCents(SubscriptionPlan $plan): int
    {
        return $plan->recurringAmountCents();
    }

    protected function buildDeferredFirstPeriodEnd(SubscriptionPlan $plan): ?Carbon
    {
        if ($plan->deferred_first_period_days !== null) {
            return now()->addDays($plan->deferred_first_period_days);
        }
        if ($plan->deferred_first_period_months !== null) {
            return Carbon::now()->addMonthsNoOverflow($plan->deferred_first_period_months);
        }

        return null;
    }

    protected function persistLocalSubscription(
        User $user,
        Product $product,
        SubscriptionPlan $plan,
        string $paymentMethodId,
        string $status,
        ?Carbon $trialEndsAt,
        ?Carbon $nextBillingAt,
        ?Carbon $currentPeriodEndsAt
    ): UserSubscription {
        return UserSubscription::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'subscription_plan_id' => $plan->id,
            'product_name_snapshot' => $product->name,
            'stripe_subscription_id' => null,
            'stripe_default_payment_method_id' => $paymentMethodId,
            'status' => $status,
            'trial_ends_at' => $trialEndsAt,
            'current_period_ends_at' => $currentPeriodEndsAt,
            'next_billing_at' => $nextBillingAt,
            'cancel_at_period_end' => false,
            'plan_code_snapshot' => $plan->code,
            'payment_frequency_days_snapshot' => $plan->payment_frequency_days,
            'free_trial_days_snapshot' => $plan->free_trial_days,
            'is_joining_fees_snapshot' => $plan->is_joining_fees,
            'joining_fees_snapshot' => $plan->joining_fees,
            'is_subscription_period_snapshot' => $plan->is_subscription_period,
            'subscription_period_snapshot' => $plan->subscription_period,
        ]);
    }

    public function createIntent(Request $request)
    {
        $this->extendExecutionTime();
        $this->ensureStripeKey();

        $validated = $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $user = $request->user();
        $plan = SubscriptionPlan::query()
            ->where('id', $validated['subscription_plan_id'])
            ->where('is_active', true)
            ->firstOrFail();
        $product = $plan->product;

        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $user->refresh();
                $customerId = $this->resolveStripeCustomer($user);

                if ($this->hasRunningSubscriptionForPlan($user, (int) $plan->id)) {
                    return response()->json(['error' => 'You already have an active subscription for this plan.'], 409);
                }

                if ($plan->collectsCardViaSetupIntentOnly()) {
                    $intent = SetupIntent::create([
                        'customer' => $customerId,
                        'usage' => 'off_session',
                        'metadata' => [
                            'user_id' => (string) $user->id,
                            'subscription_plan_id' => (string) $plan->id,
                        ],
                        'payment_method_types' => ['card'],
                    ]);

                    return response()->json([
                        'mode' => 'trial_setup_intent',
                        'clientSecret' => $intent->client_secret,
                    ]);
                }

                $amount = $this->firstChargeAmountCents($plan);
                $intent = PaymentIntent::create([
                    'amount' => $amount,
                    'currency' => $plan->stripeCurrency(),
                    'customer' => $customerId,
                    'setup_future_usage' => 'off_session',
                    'metadata' => [
                        'user_id' => (string) $user->id,
                        'subscription_plan_id' => (string) $plan->id,
                        'billing_type' => LocalSubscriptionBilling::BILLING_TYPE_INITIAL,
                    ],
                    'payment_method_types' => ['card'],
                ]);

                $label = 'Pay $' . number_format($amount / 100, 2) . ' now, then your plan renews automatically.';

                return response()->json([
                    'mode' => 'plan_initial_payment',
                    'clientSecret' => $intent->client_secret,
                    'message' => $label,
                ]);
            } catch (ApiErrorException $e) {
                if ($this->isStripeCurrencyConflict($e) && $attempt === 0) {
                    $this->recreateStripeCustomer($user);
                    continue;
                }

                return response()->json(['error' => $e->getMessage()], 400);
            }
        }

        return response()->json(['error' => 'Unable to initialize subscription payment.'], 500);
    }

    public function confirmPlanSubscription(Request $request)
    {
        $this->extendExecutionTime();
        $this->ensureStripeKey();

        $validated = $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'payment_intent_id' => 'required|string',
        ]);

        $user = $request->user();
        $plan = SubscriptionPlan::query()
            ->where('id', $validated['subscription_plan_id'])
            ->where('is_active', true)
            ->firstOrFail();

        if ($plan->collectsCardViaSetupIntentOnly()) {
            return response()->json(['error' => 'This plan uses the trial setup flow.'], 422);
        }

        $product = $plan->product;
        $paymentIntent = PaymentIntent::retrieve($validated['payment_intent_id']);

        if ($paymentIntent->status !== 'succeeded') {
            return response()->json(['error' => 'Payment is not completed.'], 422);
        }

        if ((string) ($paymentIntent->metadata->user_id ?? '') !== (string) $user->id) {
            return response()->json(['error' => 'Invalid payment for this user.'], 403);
        }

        if ((string) ($paymentIntent->metadata->subscription_plan_id ?? '') !== (string) $plan->id) {
            return response()->json(['error' => 'Payment does not match the selected plan.'], 422);
        }

        if (empty($paymentIntent->payment_method)) {
            return response()->json(['error' => 'No payment method on this payment.'], 422);
        }

        $periodEnd = $this->buildDeferredFirstPeriodEnd($plan);
        if ($periodEnd === null) {
            return response()->json(['error' => 'This plan is not configured for the initial-payment checkout flow.'], 422);
        }

        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $user->refresh();
                $customerId = $this->resolveStripeCustomer($user);

                if (!empty($paymentIntent->customer) && $paymentIntent->customer !== $customerId) {
                    return response()->json(['error' => 'This payment belongs to a different customer.'], 403);
                }

                if ($this->hasRunningSubscriptionForPlan($user, (int) $plan->id)) {
                    return response()->json(['error' => 'You already have an active subscription for this plan.'], 409);
                }

                $this->attachPaymentMethodToCustomer($paymentIntent->payment_method, $customerId);
                $this->setCustomerDefaultPaymentMethod($customerId, $paymentIntent->payment_method);

                $userSubscription = $this->persistLocalSubscription(
                    $user,
                    $product,
                    $plan,
                    $paymentIntent->payment_method,
                    'active',
                    null,
                    $periodEnd,
                    $periodEnd
                );

                StripeTimeouts::forgetUserSubscriptionCaches($user);

                return response()->json([
                    'success' => true,
                    'subscriptionId' => $userSubscription->id,
                    'redirect' => route('subscriptions.show', $userSubscription),
                    'message' => $plan->title . ' — subscription started successfully.',
                ]);
            } catch (ApiErrorException $e) {
                if ($this->isStripeCurrencyConflict($e) && $attempt === 0) {
                    $this->recreateStripeCustomer($user);
                    continue;
                }

                return response()->json(['error' => $e->getMessage()], 400);
            }
        }

        return response()->json(['error' => 'Unable to create subscription after payment.'], 500);
    }

    public function confirmTrialMonthly(Request $request)
    {
        $this->extendExecutionTime();
        $this->ensureStripeKey();

        $validated = $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'setup_intent_id' => 'required|string',
        ]);

        $user = $request->user();
        $plan = SubscriptionPlan::query()
            ->where('id', $validated['subscription_plan_id'])
            ->where('is_active', true)
            ->firstOrFail();

        if (!$plan->collectsCardViaSetupIntentOnly()) {
            return response()->json(['error' => 'This plan does not use the trial setup flow.'], 422);
        }

        $trialDays = (int) $plan->stripe_trial_period_days;
        if ($trialDays < 1) {
            return response()->json(['error' => 'Plan trial is not configured.'], 422);
        }

        $product = $plan->product;
        $setupIntent = SetupIntent::retrieve($validated['setup_intent_id']);

        if ($setupIntent->status !== 'succeeded') {
            return response()->json(['error' => 'Card setup is not completed.'], 422);
        }

        if ((string) ($setupIntent->metadata->user_id ?? '') !== (string) $user->id) {
            return response()->json(['error' => 'Invalid setup intent for this user.'], 403);
        }

        if ((string) ($setupIntent->metadata->subscription_plan_id ?? '') !== (string) $plan->id) {
            return response()->json(['error' => 'Setup intent does not match this plan.'], 422);
        }

        if (empty($setupIntent->payment_method)) {
            return response()->json(['error' => 'No payment method in setup intent.'], 422);
        }

        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $user->refresh();
                $customerId = $this->resolveStripeCustomer($user);

                if ($this->hasRunningSubscriptionForPlan($user, (int) $plan->id)) {
                    return response()->json(['error' => 'You already have an active subscription for this plan.'], 409);
                }

                $this->attachPaymentMethodToCustomer($setupIntent->payment_method, $customerId);
                $this->setCustomerDefaultPaymentMethod($customerId, $setupIntent->payment_method);

                $trialEnds = now()->addDays($trialDays);

                $userSubscription = $this->persistLocalSubscription(
                    $user,
                    $product,
                    $plan,
                    $setupIntent->payment_method,
                    'trialing',
                    $trialEnds,
                    $trialEnds,
                    $trialEnds
                );

                StripeTimeouts::forgetUserSubscriptionCaches($user);

                return response()->json([
                    'success' => true,
                    'subscriptionId' => $userSubscription->id,
                    'redirect' => route('subscriptions.show', $userSubscription),
                    'message' => 'Trial started: no charge today. After ' . $trialDays . ' days, billing begins per your plan.',
                ]);
            } catch (ApiErrorException $e) {
                if ($this->isStripeCurrencyConflict($e) && $attempt === 0) {
                    $this->recreateStripeCustomer($user);
                    continue;
                }

                return response()->json(['error' => $e->getMessage()], 400);
            }
        }

        return response()->json(['error' => 'Unable to create subscription after trial setup.'], 500);
    }

    public function show(UserSubscription $userSubscription)
    {
        if ((int) $userSubscription->user_id !== (int) auth()->id()) {
            abort(403);
        }

        $userSubscription->load(['product', 'subscriptionPlan']);
        $planLabel = $userSubscription->subscriptionPlan
            ? $userSubscription->subscriptionPlan->title
            : ucfirst(str_replace('_', ' ', $userSubscription->plan_code_snapshot));

        return view('subscription-show', [
            'userSubscription' => $userSubscription,
            'product' => $userSubscription->product,
            'planLabel' => $planLabel,
            'trialEndsAt' => $userSubscription->trial_ends_at,
            'currentPeriodEndsAt' => $userSubscription->current_period_ends_at,
        ]);
    }
}
