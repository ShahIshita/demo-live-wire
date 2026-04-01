<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Older databases may have user_subscriptions without subscription_plan_id / snapshot columns
 * because create_user_subscriptions skipped when the table already existed.
 */
class AlignUserSubscriptionsColumns extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('user_subscriptions')) {
            return;
        }

        Schema::table('user_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('user_subscriptions', 'subscription_plan_id')) {
                $table->unsignedBigInteger('subscription_plan_id')->nullable()->after('product_id');
            }

            if (!Schema::hasColumn('user_subscriptions', 'plan_code_snapshot')) {
                $table->string('plan_code_snapshot', 64)->nullable()->after('cancel_at_period_end');
            }

            if (!Schema::hasColumn('user_subscriptions', 'payment_frequency_days_snapshot')) {
                $table->unsignedInteger('payment_frequency_days_snapshot')->nullable()->after('plan_code_snapshot');
            }

            if (!Schema::hasColumn('user_subscriptions', 'free_trial_days_snapshot')) {
                $table->unsignedInteger('free_trial_days_snapshot')->nullable()->after('payment_frequency_days_snapshot');
            }

            if (!Schema::hasColumn('user_subscriptions', 'is_joining_fees_snapshot')) {
                $table->boolean('is_joining_fees_snapshot')->default(false)->after('free_trial_days_snapshot');
            }

            if (!Schema::hasColumn('user_subscriptions', 'joining_fees_snapshot')) {
                $table->decimal('joining_fees_snapshot', 15, 2)->nullable()->after('is_joining_fees_snapshot');
            }

            if (!Schema::hasColumn('user_subscriptions', 'is_subscription_period_snapshot')) {
                $table->boolean('is_subscription_period_snapshot')->default(false)->after('joining_fees_snapshot');
            }

            if (!Schema::hasColumn('user_subscriptions', 'subscription_period_snapshot')) {
                $table->unsignedTinyInteger('subscription_period_snapshot')->nullable()->after('is_subscription_period_snapshot');
            }
        });

        if (Schema::hasTable('subscription_plans') && Schema::hasColumn('user_subscriptions', 'subscription_plan_id')) {
            $this->ensureSubscriptionPlanForeignKey();
        }
    }

    protected function ensureSubscriptionPlanForeignKey(): void
    {
        $connection = Schema::getConnection();
        if ($connection->getDriverName() === 'sqlite') {
            return;
        }

        $db = $connection->getDatabaseName();
        $exists = $connection->selectOne(
            'SELECT 1 AS ok FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME = ?
             LIMIT 1',
            [$db, 'user_subscriptions', 'subscription_plan_id', 'subscription_plans']
        );

        if ($exists) {
            return;
        }

        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->foreign('subscription_plan_id')
                ->references('id')
                ->on('subscription_plans')
                ->onDelete('restrict');
        });
    }

    public function down()
    {
        // Non-destructive: do not drop columns that may contain data.
    }
}
