<?php

namespace Tests\Feature;

use App\Product;
use App\SubscriptionPlan;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPlanDbTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_plan_is_stored_and_scoped_to_product(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'description' => 'Desc',
            'price' => 9.99,
            'image' => null,
            'stock_quantity' => 5,
        ]);

        $plan = SubscriptionPlan::create([
            'product_id' => $product->id,
            'code' => 'monthly',
            'title' => 'Monthly plan',
            'sort_order' => 1,
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
            'is_active' => true,
        ]);

        $this->assertTrue($plan->product->is($product));
        $this->assertSame(30, $plan->payment_frequency_days);
        $this->assertFalse($plan->collectsCardViaSetupIntentOnly());
        $this->assertSame(100, $plan->recurringAmountCents());
    }

    public function test_trial_plan_uses_setup_intent_path(): void
    {
        $product = Product::create([
            'name' => 'P2',
            'description' => null,
            'price' => 1,
            'image' => null,
            'stock_quantity' => 1,
        ]);

        $plan = SubscriptionPlan::create([
            'product_id' => $product->id,
            'code' => 'trial_monthly',
            'title' => 'Trial',
            'sort_order' => 1,
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
            'is_active' => true,
        ]);

        $this->assertTrue($plan->collectsCardViaSetupIntentOnly());
    }

    public function test_create_intent_requires_authentication_and_valid_plan(): void
    {
        $product = Product::create([
            'name' => 'P3',
            'description' => null,
            'price' => 1,
            'image' => null,
            'stock_quantity' => 1,
        ]);

        $plan = SubscriptionPlan::create([
            'product_id' => $product->id,
            'code' => 'daily',
            'title' => 'Daily',
            'sort_order' => 1,
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
            'is_active' => true,
        ]);

        $response = $this->postJson(route('stripe.subscriptions.create-intent'), [
            'subscription_plan_id' => $plan->id,
        ]);
        $response->assertStatus(401);

        $user = factory(User::class)->create();
        $this->be($user);

        $response = $this->postJson(route('stripe.subscriptions.create-intent'), [
            'subscription_plan_id' => 999999,
        ]);
        $response->assertStatus(422);
    }
}
