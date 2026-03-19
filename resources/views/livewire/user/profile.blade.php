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
</div>
