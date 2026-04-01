<?php

namespace App\Database\Seeders;

use App\Product;
use App\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Default plans per product (matches previous hard-coded daily / monthly / trial monthly behaviour).
     *
     * @return void
     */
    public function run()
    {
        $products = Product::query()->orderBy('id')->get();

        foreach ($products as $product) {
            $defaults = [
                [
                    'code' => 'daily',
                    'title' => 'Daily — $1.00/day after first day',
                    'sort_order' => 10,
                    'payment_frequency_days' => 1,
                    'is_free_trial' => false,
                    'free_trial_days' => null,
                    'is_joining_fees' => false,
                    'joining_fees' => null,
                    'joining_fee_days' => null,
                    'is_subscription_period' => false,
                    'subscription_period' => null,
                    'recurring_price' => '1.00',
                    'currency' => 'USD',
                    'stripe_interval' => 'day',
                    'stripe_interval_count' => 1,
                    'stripe_trial_period_days' => null,
                    'deferred_first_period_days' => 1,
                    'deferred_first_period_months' => null,
                ],
                [
                    'code' => 'trial_monthly',
                    'title' => '7-day free trial, then $1.00/month',
                    'sort_order' => 20,
                    'payment_frequency_days' => 30,
                    'is_free_trial' => true,
                    'free_trial_days' => 7,
                    'is_joining_fees' => false,
                    'joining_fees' => null,
                    'joining_fee_days' => null,
                    'is_subscription_period' => false,
                    'subscription_period' => null,
                    'recurring_price' => '1.00',
                    'currency' => 'USD',
                    'stripe_interval' => 'month',
                    'stripe_interval_count' => 1,
                    'stripe_trial_period_days' => 7,
                    'deferred_first_period_days' => null,
                    'deferred_first_period_months' => null,
                ],
                [
                    'code' => 'monthly',
                    'title' => 'Monthly — $1.00/month after first month',
                    'sort_order' => 30,
                    'payment_frequency_days' => 30,
                    'is_free_trial' => false,
                    'free_trial_days' => null,
                    'is_joining_fees' => false,
                    'joining_fees' => null,
                    'joining_fee_days' => null,
                    'is_subscription_period' => false,
                    'subscription_period' => null,
                    'recurring_price' => '1.00',
                    'currency' => 'USD',
                    'stripe_interval' => 'month',
                    'stripe_interval_count' => 1,
                    'stripe_trial_period_days' => null,
                    'deferred_first_period_days' => null,
                    'deferred_first_period_months' => 1,
                ],
            ];

            foreach ($defaults as $row) {
                SubscriptionPlan::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'code' => $row['code'],
                    ],
                    array_merge($row, [
                        'product_id' => $product->id,
                        'is_active' => true,
                    ])
                );
            }
        }
    }
}
