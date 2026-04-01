<?php

namespace App\Console\Commands;

use App\Support\StripeTimeouts;
use App\User;
use App\UserSubscription;
use Illuminate\Console\Command;

/**
 * Deletes all rows in user_subscriptions and clears subscription UI caches.
 * Does not delete subscription_plans (plan catalog). Does not remove Stripe objects.
 */
class ClearUserSubscriptions extends Command
{
    protected $signature = 'subscriptions:clear-local';

    protected $description = 'Delete all user_subscriptions rows and clear subscription caches (for local testing)';

    public function handle(): int
    {
        $count = UserSubscription::query()->count();
        UserSubscription::query()->delete();

        foreach (User::query()->cursor() as $user) {
            StripeTimeouts::forgetUserSubscriptionCaches($user);
        }

        $this->info("Deleted {$count} row(s) from user_subscriptions and cleared per-user subscription caches.");

        $this->warn('If users still see "already subscribed", legacy Stripe Subscriptions may exist — cancel them in Stripe Dashboard or clear test customers.');

        return 0;
    }
}
