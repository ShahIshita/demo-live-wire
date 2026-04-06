<?php

namespace App\Support;

final class LocalSubscriptionBilling
{
    /** First PaymentIntent after user confirms checkout (plan requires upfront payment). */
    public const BILLING_TYPE_INITIAL = 'subscription_initial';

    /** Off-session PaymentIntent created by the renewal Artisan command / scheduler. */
    public const BILLING_TYPE_RENEWAL = 'subscription_renewal';

    private function __construct()
    {
    }
}
