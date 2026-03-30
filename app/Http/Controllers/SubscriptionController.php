<?php

namespace App\Http\Controllers;

use App\Product;
use App\Support\StripeTimeouts;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Product as StripeProduct;
use Stripe\SetupIntent;
use Stripe\Stripe;
use Stripe\Subscription;

class SubscriptionController extends Controller
{
    /** All amounts in smallest currency unit (cents for USD). */
    private const CURRENCY = 'usd';

    /** $1.00 / day (100 cents) — matches prior “100” demo pricing in dollars. */
    private const AMOUNT_DAILY_CENTS = 100;

    /** $1.00 / month (100 cents). */
    private const AMOUNT_MONTHLY_CENTS = 100;

    private const TRIAL_DAYS = 7;

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
                'app_currency' => self::CURRENCY,
            ],
        ]);

        $user->stripe_customer_id = $customer->id;
        $user->save();

        return $customer->id;
    }

    /**
     * Stripe does not mix currencies on one customer (e.g. prior INR vs USD).
     * Clear the stored id so the next call creates a fresh customer in the app currency.
     */
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

    /**
     * Stripe requires the payment method to be attached to the customer before using it as
     * default_payment_method on Subscription, or when the API validates ownership.
     */
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
            // Already attached to this customer in a race, or Stripe idempotent edge case.
            $pm = PaymentMethod::retrieve($paymentMethodId);
            if (empty($pm->customer) || $pm->customer !== $customerId) {
                throw $e;
            }
        }
    }

    /**
     * @param string $planType daily|monthly (trial_monthly uses monthly recurring after trial)
     */
    protected function subscriptionItemForPlan(Product $product, string $planType): array
    {
        $stripeProduct = StripeProduct::create([
            'name' => $product->name . ' Subscription (' . $planType . ')',
            'metadata' => [
                'local_product_id' => (string) $product->id,
            ],
        ]);

        $recurring = ['interval' => 'month'];
        $unitAmount = self::AMOUNT_MONTHLY_CENTS;

        if ($planType === 'daily') {
            $recurring = ['interval' => 'day'];
            $unitAmount = self::AMOUNT_DAILY_CENTS;
        }

        return [
            // Inline price_data removes extra Stripe Product+Price API calls and reduces request time.
            'price_data' => [
                'currency' => self::CURRENCY,
                'unit_amount' => $unitAmount,
                'recurring' => $recurring,
                'product' => $stripeProduct->id,
            ],
            'quantity' => 1,
        ];
    }

    protected function hasRunningSubscriptionForProduct(string $customerId, int $productId): bool
    {
        $subscriptions = Subscription::all([
            'customer' => $customerId,
            'status' => 'all',
            'limit' => 100,
        ]);

        foreach ($subscriptions->data as $subscription) {
            $subscriptionProductId = (int) ($subscription->metadata->product_id ?? 0);
            if ($subscriptionProductId !== $productId) {
                continue;
            }

            $isRunning = in_array($subscription->status, ['active', 'trialing', 'past_due', 'unpaid'], true);
            $isScheduledToCancel = (bool) ($subscription->cancel_at_period_end ?? false);

            if ($isRunning && !$isScheduledToCancel) {
                return true;
            }
        }

        return false;
    }

    protected function firstChargeAmountForPlan(string $planType): int
    {
        return $planType === 'daily'
            ? self::AMOUNT_DAILY_CENTS
            : self::AMOUNT_MONTHLY_CENTS;
    }

    public function createIntent(Request $request)
    {
        $this->extendExecutionTime();
        $this->ensureStripeKey();

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'plan_type' => 'required|in:daily,monthly,trial_monthly',
        ]);

        $user = $request->user();
        $product = Product::findOrFail($validated['product_id']);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $user->refresh();
                $customerId = $this->resolveStripeCustomer($user);

                if ($this->hasRunningSubscriptionForProduct($customerId, (int) $product->id)) {
                    return response()->json(['error' => 'You already have an active subscription for this product.'], 409);
                }

                if ($validated['plan_type'] === 'trial_monthly') {
                    $intent = SetupIntent::create([
                        'customer' => $customerId,
                        'usage' => 'off_session',
                        'metadata' => [
                            'user_id' => (string) $user->id,
                            'product_id' => (string) $product->id,
                            'plan_type' => 'trial_monthly',
                        ],
                        'payment_method_types' => ['card'],
                    ]);

                    return response()->json([
                        'mode' => 'trial_setup_intent',
                        'clientSecret' => $intent->client_secret,
                    ]);
                }

                $amount = $this->firstChargeAmountForPlan($validated['plan_type']);
                $intent = PaymentIntent::create([
                    'amount' => $amount,
                    'currency' => self::CURRENCY,
                    'customer' => $customerId,
                    'setup_future_usage' => 'off_session',
                    'metadata' => [
                        'user_id' => (string) $user->id,
                        'product_id' => (string) $product->id,
                        'plan_type' => $validated['plan_type'],
                    ],
                    'payment_method_types' => ['card'],
                ]);

                $label = $validated['plan_type'] === 'daily'
                    ? 'Pay $1.00 now, then your daily plan will continue automatically.'
                    : 'Pay $1.00 now, then your monthly plan will continue automatically.';

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

    /**
     * After daily/monthly first payment succeeds, create the recurring subscription.
     */
    public function confirmPlanSubscription(Request $request)
    {
        $this->extendExecutionTime();
        $this->ensureStripeKey();

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'plan_type' => 'required|in:daily,monthly',
            'payment_intent_id' => 'required|string',
        ]);

        $user = $request->user();
        $product = Product::findOrFail($validated['product_id']);
        $paymentIntent = PaymentIntent::retrieve($validated['payment_intent_id']);

        if ($paymentIntent->status !== 'succeeded') {
            return response()->json(['error' => 'Payment is not completed.'], 422);
        }

        if ((string) ($paymentIntent->metadata->user_id ?? '') !== (string) $user->id) {
            return response()->json(['error' => 'Invalid payment for this user.'], 403);
        }

        if ((string) ($paymentIntent->metadata->product_id ?? '') !== (string) $product->id) {
            return response()->json(['error' => 'Payment does not match this product.'], 422);
        }

        if ((string) ($paymentIntent->metadata->plan_type ?? '') !== (string) $validated['plan_type']) {
            return response()->json(['error' => 'Payment does not match the selected plan.'], 422);
        }

        if (empty($paymentIntent->payment_method)) {
            return response()->json(['error' => 'No payment method on this payment.'], 422);
        }

        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $user->refresh();
                $customerId = $this->resolveStripeCustomer($user);

                if (!empty($paymentIntent->customer) && $paymentIntent->customer !== $customerId) {
                    return response()->json(['error' => 'This payment belongs to a different customer.'], 403);
                }

                if ($this->hasRunningSubscriptionForProduct($customerId, (int) $product->id)) {
                    return response()->json(['error' => 'You already have an active subscription for this product.'], 409);
                }

                $this->attachPaymentMethodToCustomer($paymentIntent->payment_method, $customerId);

                $trialEnd = $validated['plan_type'] === 'daily'
                    ? now()->addDay()->timestamp
                    : Carbon::now()->addMonthNoOverflow()->timestamp;

                $subscription = Subscription::create([
                    'customer' => $customerId,
                    'default_payment_method' => $paymentIntent->payment_method,
                    'trial_end' => $trialEnd,
                    'items' => [
                        $this->subscriptionItemForPlan($product, $validated['plan_type']),
                    ],
                    'payment_settings' => [
                        'save_default_payment_method' => 'on_subscription',
                    ],
                    'metadata' => [
                        'user_id' => $user->id,
                        'product_id' => $product->id,
                        'plan_type' => $validated['plan_type'],
                    ],
                ]);

                StripeTimeouts::forgetUserSubscriptionCaches($user);

                $planLabel = $validated['plan_type'] === 'daily' ? 'Daily' : 'Monthly';

                return response()->json([
                    'success' => true,
                    'subscriptionId' => $subscription->id,
                    'redirect' => route('subscriptions.show', $subscription->id),
                    'message' => $planLabel . ' subscription started successfully.',
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

    /**
     * After SetupIntent succeeds, start 7-day trial then monthly billing (USD).
     */
    public function confirmTrialMonthly(Request $request)
    {
        $this->extendExecutionTime();
        $this->ensureStripeKey();

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'setup_intent_id' => 'required|string',
        ]);

        $user = $request->user();
        $product = Product::findOrFail($validated['product_id']);

        $setupIntent = SetupIntent::retrieve($validated['setup_intent_id']);

        if ($setupIntent->status !== 'succeeded') {
            return response()->json(['error' => 'Card setup is not completed.'], 422);
        }

        if ((string) ($setupIntent->metadata->user_id ?? '') !== (string) $user->id) {
            return response()->json(['error' => 'Invalid setup intent for this user.'], 403);
        }

        if ((string) ($setupIntent->metadata->product_id ?? '') !== (string) $product->id) {
            return response()->json(['error' => 'Setup intent does not match this product.'], 422);
        }

        if ((string) ($setupIntent->metadata->plan_type ?? '') !== 'trial_monthly') {
            return response()->json(['error' => 'Setup intent does not match trial plan.'], 422);
        }

        if (empty($setupIntent->payment_method)) {
            return response()->json(['error' => 'No payment method in setup intent.'], 422);
        }

        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $user->refresh();
                $customerId = $this->resolveStripeCustomer($user);

                if ($this->hasRunningSubscriptionForProduct($customerId, (int) $product->id)) {
                    return response()->json(['error' => 'You already have an active subscription for this product.'], 409);
                }

                // Ensure setup card is attached to customer before creating trial subscription.
                $this->attachPaymentMethodToCustomer($setupIntent->payment_method, $customerId);

                $subscription = Subscription::create([
                    'customer' => $customerId,
                    'default_payment_method' => $setupIntent->payment_method,
                    'trial_period_days' => self::TRIAL_DAYS,
                    'items' => [
                        $this->subscriptionItemForPlan($product, 'monthly'),
                    ],
                    'payment_settings' => [
                        'save_default_payment_method' => 'on_subscription',
                    ],
                    'metadata' => [
                        'user_id' => $user->id,
                        'product_id' => $product->id,
                        'plan_type' => 'trial_monthly',
                    ],
                ]);

                StripeTimeouts::forgetUserSubscriptionCaches($user);

                return response()->json([
                    'success' => true,
                    'subscriptionId' => $subscription->id,
                    'redirect' => route('subscriptions.show', $subscription->id),
                    'message' => 'Trial started: no charge today. After ' . self::TRIAL_DAYS . ' days, billing is $1.00/month.',
                ]);
            } catch (ApiErrorException $e) {
                if ($this->isStripeCurrencyConflict($e) && $attempt === 0) {
                    $this->recreateStripeCustomer($user);
                    continue;
                }

                return response()->json(['error' => $e->getMessage()], 400);
            }
        }

        return response()->json(['error' => 'Unable to create subscription after trial payment.'], 500);
    }

    public function show(string $subscriptionId)
    {
        $this->extendExecutionTime();
        $this->ensureStripeKey();

        $user = auth()->user();
        if (empty($user->stripe_customer_id)) {
            abort(404);
        }

        $subscription = Subscription::retrieve($subscriptionId);
        if (($subscription->customer ?? null) !== $user->stripe_customer_id) {
            abort(403);
        }

        $productId = (int) ($subscription->metadata->product_id ?? 0);
        $product = $productId ? Product::find($productId) : null;

        $planType = $subscription->metadata->plan_type ?? 'monthly';
        $planLabel = [
            'daily' => 'Daily',
            'monthly' => 'Monthly',
            'trial_monthly' => '7-day trial + monthly',
        ][$planType] ?? ucfirst(str_replace('_', ' ', $planType));

        return view('subscription-show', [
            'subscription' => $subscription,
            'product' => $product,
            'planLabel' => $planLabel,
            'trialEndsAt' => !empty($subscription->trial_end) ? Carbon::createFromTimestamp($subscription->trial_end) : null,
            'currentPeriodEndsAt' => !empty($subscription->current_period_end) ? Carbon::createFromTimestamp($subscription->current_period_end) : null,
        ]);
    }
}
