<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Subscriptions are managed in the app DB; Stripe sees PaymentIntents + SetupIntents only.
 */
class UserSubscriptionsLocalPaymentIntentBilling extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('user_subscriptions')) {
            return;
        }

        Schema::table('user_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('user_subscriptions', 'stripe_default_payment_method_id')) {
                $table->string('stripe_default_payment_method_id')->nullable()->after('stripe_subscription_id');
            }
            if (!Schema::hasColumn('user_subscriptions', 'next_billing_at')) {
                $table->timestamp('next_billing_at')->nullable()->after('current_period_ends_at');
            }
        });

        try {
            DB::statement('ALTER TABLE user_subscriptions MODIFY stripe_subscription_id VARCHAR(255) NULL');
        } catch (\Throwable $e) {
            // Column might already be nullable or DB grammar differs.
        }
    }

    public function down()
    {
        //
    }
}
