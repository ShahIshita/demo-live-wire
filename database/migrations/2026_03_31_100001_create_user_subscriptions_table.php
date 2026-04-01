<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserSubscriptionsTable extends Migration
{
    /**
     * Local record of each subscription (plan terms + lifecycle); Stripe id is only for billing API.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('user_subscriptions')) {
            return;
        }

        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('subscription_plan_id');

            $table->string('stripe_subscription_id')->unique();

            $table->string('status', 32);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_ends_at')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);

            $table->string('plan_code_snapshot', 64);
            $table->unsignedInteger('payment_frequency_days_snapshot');
            $table->unsignedInteger('free_trial_days_snapshot')->nullable();
            $table->boolean('is_joining_fees_snapshot')->default(false);
            $table->decimal('joining_fees_snapshot', 15, 2)->nullable();
            $table->boolean('is_subscription_period_snapshot')->default(false);
            $table->unsignedTinyInteger('subscription_period_snapshot')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('subscription_plan_id')->references('id')->on('subscription_plans')->onDelete('restrict');
            $table->index(['user_id', 'product_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_subscriptions');
    }
}
