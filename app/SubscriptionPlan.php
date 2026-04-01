<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'product_id',
        'code',
        'title',
        'sort_order',
        'payment_frequency_days',
        'is_free_trial',
        'free_trial_days',
        'is_joining_fees',
        'joining_fees',
        'joining_fee_days',
        'is_subscription_period',
        'subscription_period',
        'recurring_price',
        'currency',
        'stripe_interval',
        'stripe_interval_count',
        'stripe_trial_period_days',
        'deferred_first_period_days',
        'deferred_first_period_months',
        'is_active',
    ];

    protected $casts = [
        'is_free_trial' => 'boolean',
        'is_joining_fees' => 'boolean',
        'is_subscription_period' => 'boolean',
        'is_active' => 'boolean',
        'recurring_price' => 'decimal:2',
        'joining_fees' => 'decimal:2',
        'payment_frequency_days' => 'integer',
        'free_trial_days' => 'integer',
        'joining_fee_days' => 'integer',
        'subscription_period' => 'integer',
        'sort_order' => 'integer',
        'stripe_interval_count' => 'integer',
        'stripe_trial_period_days' => 'integer',
        'deferred_first_period_days' => 'integer',
        'deferred_first_period_months' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function userSubscriptions()
    {
        return $this->hasMany(UserSubscription::class, 'subscription_plan_id');
    }

    /**
     * Next renewal timestamp after a successful charge (used with local DB + PaymentIntents).
     */
    public function computeNextBillingAfter(Carbon $from): Carbon
    {
        $interval = strtolower((string) $this->stripe_interval);
        $count = max(1, (int) $this->stripe_interval_count);

        if ($interval === 'day') {
            return $from->copy()->addDays($count);
        }
        if ($interval === 'week') {
            return $from->copy()->addWeeks($count);
        }
        if ($interval === 'month') {
            return $from->copy()->addMonthsNoOverflow($count);
        }
        if ($interval === 'year') {
            return $from->copy()->addYears($count);
        }

        return $from->copy()->addDays(max(1, (int) $this->payment_frequency_days));
    }

    /**
     * Card setup without first PaymentIntent (free trial until first renewal job runs).
     */
    public function collectsCardViaSetupIntentOnly(): bool
    {
        return (int) ($this->stripe_trial_period_days ?? 0) > 0;
    }

    public function recurringAmountCents(): int
    {
        return (int) round((float) $this->recurring_price * 100);
    }

    public function stripeCurrency(): string
    {
        return strtolower($this->currency ?: 'usd');
    }
}
