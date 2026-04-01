<?php

namespace App\Console\Commands;

use App\Support\StripeTimeouts;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Busts subscription UI caches. New subscriptions are DB + PaymentIntent only (no Stripe Subscription API).
 */
class SyncStripeSubscriptions extends Command
{
    protected $signature = 'subscriptions:sync-stripe
                            {--user= : Only process this user id (for testing)}
                            {--chunk=25 : Max users to process per run (round-robin over all users)}';

    protected $description = 'Clear subscription-related caches for users (Stripe subscriptions are no longer used for new signups)';

    private const OFFSET_CACHE_KEY = 'subscriptions:sync-stripe:offset';

    public function handle(): int
    {
        if ($userId = $this->option('user')) {
            $user = User::find($userId);
            if (!$user) {
                $this->error('User not found.');

                return 1;
            }
            StripeTimeouts::forgetUserSubscriptionCaches($user);
            $this->info("Cleared caches for user {$user->id}.");

            return 0;
        }

        $chunkSize = max(1, (int) $this->option('chunk'));
        $query = User::query()->orderBy('id');
        $total = $query->count();

        if ($total === 0) {
            $this->info('No users.');

            return 0;
        }

        $offset = (int) Cache::get(self::OFFSET_CACHE_KEY, 0);
        if ($offset >= $total) {
            $offset = 0;
        }

        $users = (clone $query)->skip($offset)->take($chunkSize)->get();

        if ($users->isEmpty()) {
            $users = $query->take($chunkSize)->get();
            $offset = 0;
        }

        foreach ($users as $user) {
            StripeTimeouts::forgetUserSubscriptionCaches($user);
        }

        $nextOffset = $offset + $users->count();
        if ($nextOffset >= $total) {
            $nextOffset = 0;
        }

        Cache::put(self::OFFSET_CACHE_KEY, $nextOffset, now()->addDays(7));

        $this->info(sprintf(
            'Cleared subscription caches for %d user(s). Offset %d → %d (of %d).',
            $users->count(),
            $offset,
            $nextOffset,
            $total
        ));

        return 0;
    }
}
