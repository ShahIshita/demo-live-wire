<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'subscription_plan_id',
        'product_name_snapshot',
        'stripe_subscription_id',
        'stripe_default_payment_method_id',
        'status',
        'trial_ends_at',
        'current_period_ends_at',
        'next_billing_at',
        'cancel_at_period_end',
        'plan_code_snapshot',
        'payment_frequency_days_snapshot',
        'free_trial_days_snapshot',
        'is_joining_fees_snapshot',
        'joining_fees_snapshot',
        'is_subscription_period_snapshot',
        'subscription_period_snapshot',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'current_period_ends_at' => 'datetime',
        'next_billing_at' => 'datetime',
        'cancel_at_period_end' => 'boolean',
        'is_joining_fees_snapshot' => 'boolean',
        'is_subscription_period_snapshot' => 'boolean',
        'joining_fees_snapshot' => 'decimal:2',
        'payment_frequency_days_snapshot' => 'integer',
        'free_trial_days_snapshot' => 'integer',
        'subscription_period_snapshot' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function isRunning(): bool
    {
        if ($this->cancel_at_period_end) {
            return false;
        }

        return in_array($this->status, ['active', 'trialing', 'past_due', 'unpaid'], true);
    }
}
