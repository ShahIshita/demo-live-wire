<?php

namespace App\Http\Livewire\User;

use App\Support\StripeTimeouts;
use App\UserSubscription;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

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

    /**
     * Subscription list from local DB only (no Stripe Subscription API calls).
     */
    protected function getSubscriptionsWithDetails(): array
    {
        $user = auth()->user();
        $details = [];
        $this->subscriptionError = null;

        $cacheKey = StripeTimeouts::cacheKeyProfileSubscriptions($user);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $rows = UserSubscription::query()
            ->where('user_id', $user->id)
            ->with(['product', 'subscriptionPlan'])
            ->orderByDesc('created_at')
            ->get();

        foreach ($rows as $row) {
            if (!in_array($row->status, ['active', 'trialing', 'past_due', 'unpaid'], true)) {
                continue;
            }

            $trialEnd = $row->trial_ends_at;
            $currentPeriodEnd = $row->current_period_ends_at;
            $plan = $row->subscriptionPlan;

            $details[] = [
                'id' => $row->id,
                'stripe_legacy_id' => $row->stripe_subscription_id,
                'status' => $row->status,
                'product_id' => $row->product_id,
                'product_name' => $row->product ? $row->product->name : 'Subscription Product',
                'plan_type' => $row->plan_code_snapshot,
                'plan_title' => $plan ? $plan->title : ucfirst(str_replace('_', ' ', $row->plan_code_snapshot)),
                'payment_frequency_days' => $row->payment_frequency_days_snapshot,
                'free_trial_days_snapshot' => $row->free_trial_days_snapshot,
                'is_joining_fees_snapshot' => $row->is_joining_fees_snapshot,
                'joining_fees_snapshot' => $row->joining_fees_snapshot,
                'is_subscription_period_snapshot' => $row->is_subscription_period_snapshot,
                'subscription_period_snapshot' => $row->subscription_period_snapshot,
                'trial_days_left' => $trialEnd ? max(0, now()->diffInDays($trialEnd, false)) : null,
                'trial_ends_at' => $trialEnd,
                'current_period_ends_at' => $currentPeriodEnd,
                'next_billing_at' => $row->next_billing_at,
                'auto_renew' => !$row->cancel_at_period_end,
            ];
        }

        Cache::put($cacheKey, $details, 90);

        return $details;
    }

    public function cancelSubscription(string $subscriptionId)
    {
        $user = auth()->user();

        if (ctype_digit((string) $subscriptionId)) {
            $local = UserSubscription::query()
                ->where('id', (int) $subscriptionId)
                ->where('user_id', $user->id)
                ->first();

            if ($local) {
                $local->update(['cancel_at_period_end' => true]);
                StripeTimeouts::forgetUserSubscriptionCaches($user);
                session()->flash('message', 'Subscription will be canceled at period end.');

                return;
            }
        }

        session()->flash('message', 'Subscription not found or already removed.');
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
