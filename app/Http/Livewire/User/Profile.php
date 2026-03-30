<?php

namespace App\Http\Livewire\User;

use App\Product;
use App\Support\StripeTimeouts;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\Subscription;

class Profile extends Component
{
    public $activeTab = 'orders';
    public $subscriptionError = null;

    public function mount()
    {
        if (request()->has('tab') && in_array(request('tab'), ['orders', 'address', 'subscription'])) {
            $this->activeTab = request('tab');
        }
    }

    public function setTab($tab)
    {
        if (in_array($tab, ['orders', 'address', 'subscription'])) {
            $this->activeTab = $tab;
        }
    }

    protected function setStripeApiKey(): bool
    {
        $secret = config('services.stripe.secret') ?: env('STRIPE_SECRET');
        if (empty($secret)) {
            $this->subscriptionError = 'Stripe is not configured right now.';
            return false;
        }

        Stripe::setApiKey($secret);
        StripeTimeouts::apply();

        return true;
    }

    protected function getSubscriptionsWithDetails(): array
    {
        $user = auth()->user();
        $details = [];
        $this->subscriptionError = null;

        if (empty($user->stripe_customer_id)) {
            return $details;
        }

        if (!$this->setStripeApiKey()) {
            return $details;
        }

        $cacheKey = StripeTimeouts::cacheKeyProfileSubscriptions($user);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $subscriptions = Subscription::all([
                'customer' => $user->stripe_customer_id,
                'status' => 'all',
                'limit' => 100,
            ]);
        } catch (ApiErrorException $e) {
            $this->subscriptionError = 'We could not load your subscription details right now.';

            return [];
        }

        $metadataProductIds = [];
        foreach ($subscriptions->data as $subscription) {
            $productId = $subscription->metadata->product_id ?? null;
            if (!empty($productId)) {
                $metadataProductIds[] = (int) $productId;
            }
        }

        $productNames = Product::whereIn('id', $metadataProductIds)->pluck('name', 'id')->toArray();

        foreach ($subscriptions->data as $subscription) {
            if (!in_array($subscription->status, ['active', 'trialing', 'past_due', 'unpaid'])) {
                continue;
            }

            $productId = (int) ($subscription->metadata->product_id ?? 0);
            $trialEnd = !empty($subscription->trial_end) ? Carbon::createFromTimestamp($subscription->trial_end) : null;
            $currentPeriodEnd = !empty($subscription->current_period_end) ? Carbon::createFromTimestamp($subscription->current_period_end) : null;

            $details[] = [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'product_id' => $productId ?: null,
                'product_name' => $productNames[$productId] ?? 'Subscription Product',
                'plan_type' => $subscription->metadata->plan_type ?? 'standard',
                'trial_days_left' => $trialEnd ? max(0, now()->diffInDays($trialEnd, false)) : null,
                'trial_ends_at' => $trialEnd,
                'current_period_ends_at' => $currentPeriodEnd,
                'auto_renew' => !$subscription->cancel_at_period_end,
            ];
        }

        Cache::put($cacheKey, $details, 90);

        return $details;
    }

    public function cancelSubscription(string $subscriptionId)
    {
        $user = auth()->user();
        if (empty($user->stripe_customer_id) || !$this->setStripeApiKey()) {
            return;
        }

        try {
            $subscription = Subscription::retrieve($subscriptionId);

            if ($subscription->customer !== $user->stripe_customer_id) {
                session()->flash('message', 'Invalid subscription selected.');
                return;
            }

            Subscription::update($subscriptionId, [
                'cancel_at_period_end' => true,
            ]);

            StripeTimeouts::forgetUserSubscriptionCaches($user);

            session()->flash('message', 'Subscription will be canceled at period end.');
        } catch (ApiErrorException $e) {
            session()->flash('message', 'Unable to cancel subscription right now.');
        }
    }

    public function render()
    {
        $orders = auth()->user()->orders()->with(['address', 'items.product'])->orderBy('created_at', 'desc')->get();
        $subscriptionDetails = $this->getSubscriptionsWithDetails();
        $subscriptionAlert = null;

        foreach ($subscriptionDetails as $subscription) {
            if (!$subscription['auto_renew'] && $subscription['current_period_ends_at']) {
                $subscriptionAlert = 'Auto-renew is off for one or more plans. Your plan expires on ' . $subscription['current_period_ends_at']->format('M d, Y') . '.';
                break;
            }

            if ($subscription['status'] === 'trialing' && $subscription['trial_days_left'] !== null && $subscription['trial_days_left'] <= 3) {
                $subscriptionAlert = 'Your trial is expiring in ' . $subscription['trial_days_left'] . ' day(s).';
                break;
            }
        }

        if ($this->subscriptionError && !$subscriptionAlert) {
            $subscriptionAlert = $this->subscriptionError;
        }

        return view('livewire.user.profile', [
            'orders' => $orders,
            'subscriptionDetails' => $subscriptionDetails,
            'subscriptionAlert' => $subscriptionAlert,
        ]);
    }
}
