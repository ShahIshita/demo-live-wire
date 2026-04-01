<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionPlansTable extends Migration
{
    /**
     * Catalog of subscription offers per product (terms live here, not in Stripe metadata).
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('subscription_plans')) {
            return;
        }

        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id');
            $table->string('code', 64);
            $table->string('title');
            $table->unsignedInteger('sort_order')->default(0);

            $table->unsignedInteger('payment_frequency_days')->comment('Business reference: length of one billing cycle in days');
            $table->boolean('is_free_trial')->default(false);
            $table->unsignedInteger('free_trial_days')->nullable();
            $table->boolean('is_joining_fees')->default(false);
            $table->decimal('joining_fees', 15, 2)->nullable();
            $table->unsignedInteger('joining_fee_days')->nullable();
            $table->boolean('is_subscription_period')->default(false);
            $table->unsignedTinyInteger('subscription_period')->nullable()->comment('Fixed commitment period units when is_subscription_period is set');

            $table->decimal('recurring_price', 15, 2);
            $table->char('currency', 3)->default('USD');
            $table->string('stripe_interval', 16)->comment('day, week, month, year');
            $table->unsignedSmallInteger('stripe_interval_count')->default(1);

            /** When set, Stripe subscription uses trial_period_days (card collected up front, no immediate charge). */
            $table->unsignedInteger('stripe_trial_period_days')->nullable();
            /** Deferred first invoice: trial_end = now + N days (e.g. daily plan). */
            $table->unsignedInteger('deferred_first_period_days')->nullable();
            /** Deferred first invoice: trial_end = now + N calendar months (e.g. monthly plan). */
            $table->unsignedTinyInteger('deferred_first_period_months')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'code']);
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_plans');
    }
}
