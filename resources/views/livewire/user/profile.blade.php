<div>
    <div class="profile-header">
        <h2>My Profile</h2>
        <p class="profile-subtitle">{{ auth()->user()->name }} — {{ auth()->user()->email }}</p>
    </div>

    <div class="profile-tabs">
        <button type="button" class="profile-tab {{ $activeTab === 'orders' ? 'active' : '' }}" wire:click="setTab('orders')">
            Orders Placed
        </button>
        <button type="button" class="profile-tab {{ $activeTab === 'address' ? 'active' : '' }}" wire:click="setTab('address')">
            Addresses
        </button>
        <button type="button" class="profile-tab {{ $activeTab === 'subscription' ? 'active' : '' }}" wire:click="setTab('subscription')">
            Subscription
        </button>
    </div>

    @if ($activeTab === 'orders')
        <div class="profile-content card">
            <h3>Orders Placed</h3>
            @if ($orders->isEmpty())
                <p class="empty-state">No orders yet. <a href="{{ route('dashboard') }}">Start shopping</a></p>
            @else
                <div class="orders-list">
                    @foreach ($orders as $order)
                        <div class="order-card">
                            <div class="order-card-header">
                                <strong>Order #{{ $order->id }}</strong>
                                <span class="order-status-badge {{ $order->status }}">{{ ucfirst($order->status) }}</span>
                                <span class="order-date">{{ $order->created_at->format('M d, Y') }}</span>
                            </div>
                            <div class="order-card-body">
                                <p><strong>Total:</strong> ${{ number_format($order->total, 2) }}</p>
                                <p><strong>Delivery to:</strong> {{ $order->address->address_line1 }}, {{ $order->address->city }}</p>
                                @if ($order->delivery_date)
                                    <p><strong>Expected delivery:</strong> {{ $order->delivery_date->format('M d, Y') }}</p>
                                @endif
                                @if ($order->delivery_tracking_number)
                                    <p class="delivery-tracking">
                                        <strong>Tracking:</strong>
                                        @if ($order->delivery_carrier)
                                            {{ $order->delivery_carrier }} —
                                        @endif
                                        {{ $order->delivery_tracking_number }}
                                    </p>
                                @endif
                            </div>
                            <div class="order-card-actions">
                                <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-edit">View Details</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if ($activeTab === 'address')
        <div class="profile-content">
            @livewire('user.address-manager')
        </div>
    @endif

    @if ($activeTab === 'subscription')
        <div class="profile-content card">
            <h3>Subscription</h3>

            @if ($subscriptionAlert)
                <div class="alert alert-warning">
                    {{ $subscriptionAlert }}
                </div>
            @endif

            @if (empty($subscriptionDetails))
                <p class="empty-state">No active subscription found. Open any product and choose the Subscription option to start.</p>
            @else
                <div class="orders-list">
                    @foreach ($subscriptionDetails as $subscription)
                        <div class="order-card">
                            <div class="order-card-header">
                                <strong>{{ $subscription['product_name'] }}</strong>
                                @php
                                    $statusLabel = $subscription['status'] === 'trialing'
                                        ? 'Active (Trial)'
                                        : ucfirst($subscription['status']);
                                @endphp
                                <span class="order-status-badge {{ $subscription['status'] }}">{{ $statusLabel }}</span>
                            </div>

                            <div class="order-card-body">
                                <p><strong>Product ID:</strong> {{ $subscription['product_id'] ?? 'N/A' }}</p>
                                @php
                                    $planTypeLabel = [
                                        'daily' => 'Daily',
                                        'monthly' => 'Monthly',
                                        'trial_monthly' => '7-day trial + monthly',
                                    ][$subscription['plan_type']] ?? ucfirst(str_replace('_', ' ', $subscription['plan_type']));
                                @endphp
                                <p><strong>Plan:</strong> {{ $subscription['plan_title'] ?? $planTypeLabel }}</p>
                                @if (!empty($subscription['payment_frequency_days']))
                                    <p><strong>Billing cycle (reference):</strong> every {{ $subscription['payment_frequency_days'] }} day(s)</p>
                                @endif
                                @if (!empty($subscription['free_trial_days_snapshot']))
                                    <p><strong>Plan free trial (days):</strong> {{ $subscription['free_trial_days_snapshot'] }}</p>
                                @endif
                                @if (!empty($subscription['is_joining_fees_snapshot']) && $subscription['joining_fees_snapshot'])
                                    <p><strong>Joining fee (snapshot):</strong> ${{ number_format($subscription['joining_fees_snapshot'], 2) }}</p>
                                @endif
                                @if (!empty($subscription['is_subscription_period_snapshot']) && $subscription['subscription_period_snapshot'])
                                    <p><strong>Fixed period (snapshot):</strong> {{ $subscription['subscription_period_snapshot'] }}</p>
                                @endif

                                @if ($subscription['trial_ends_at'])
                                    <p><strong>Trial ends:</strong> {{ $subscription['trial_ends_at']->format('M d, Y') }}</p>
                                    <p><strong>Trial days left:</strong> {{ $subscription['trial_days_left'] }}</p>
                                @endif

                                @if ($subscription['current_period_ends_at'])
                                    <p><strong>Current period ends:</strong> {{ $subscription['current_period_ends_at']->format('M d, Y') }}</p>
                                @endif

                                @if (!empty($subscription['next_billing_at']))
                                    <p><strong>Next billing:</strong> {{ $subscription['next_billing_at']->format('M d, Y H:i') }}</p>
                                @endif

                                <p><strong>Auto renew:</strong> {{ $subscription['auto_renew'] ? 'On' : 'Off' }}</p>
                            </div>

                            <div class="order-card-actions">
                                @if ($subscription['auto_renew'])
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-delete"
                                        wire:click="cancelSubscription('{{ $subscription['id'] }}')"
                                        onclick="return confirm('Cancel this subscription at period end?')"
                                    >
                                        Cancel Subscription
                                    </button>
                                @else
                                    <span style="font-size: 13px; color: #718096;">Cancellation scheduled</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
