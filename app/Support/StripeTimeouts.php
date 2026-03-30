<?php

namespace App\Support;

use App\User;
use Illuminate\Support\Facades\Cache;
use Stripe\HttpClient\CurlClient;

final class StripeTimeouts
{
    /** Cache keys must stay in sync with Livewire components that read them. */
    private const CACHE_SUBSCRIBED_PRODUCT_IDS = 'stripe.subscribed_product_ids.';

    private const CACHE_PROFILE_SUBSCRIPTIONS = 'stripe.profile_subscriptions.';

    /**
     * Stripe's CurlClient defaults to connect timeout 30s — the same as PHP's max_execution_time
     * in many setups, so a hung connection to api.stripe.com exhausts the whole request.
     */
    public static function apply(): void
    {
        // Keep Stripe calls below PHP's default 30s request ceiling.
        // Fast-fail is better than a fatal "Maximum execution time exceeded".
        CurlClient::instance()->setConnectTimeout(3)->setTimeout(8);
    }

    public static function cacheKeySubscribedProductIds(User $user): string
    {
        return self::CACHE_SUBSCRIBED_PRODUCT_IDS . $user->id . '.' . ($user->stripe_customer_id ?? '');
    }

    public static function cacheKeyProfileSubscriptions(User $user): string
    {
        return self::CACHE_PROFILE_SUBSCRIPTIONS . $user->id . '.' . ($user->stripe_customer_id ?? '');
    }

    public static function forgetUserSubscriptionCaches(User $user): void
    {
        Cache::forget(self::cacheKeySubscribedProductIds($user));
        Cache::forget(self::cacheKeyProfileSubscriptions($user));
    }
}
