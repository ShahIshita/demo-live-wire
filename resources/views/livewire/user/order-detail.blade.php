<div>
    <div class="order-confirmation card">
        <div class="order-success-banner">
            <h2>Order Placed Successfully!</h2>
            <p>Thank you for your order. We'll deliver to your address soon.</p>
        </div>

        <div class="order-details">
            <h3>Order #{{ $order->id }}</h3>
            <p class="order-status">Status: <strong>{{ ucfirst($order->status) }}</strong></p>

            <div class="order-address">
                <h4>Delivery Address</h4>
                {{ $order->address->address_line1 }}{{ $order->address->address_line2 ? ', ' . $order->address->address_line2 : '' }}<br>
                {{ $order->address->city }}, {{ $order->address->state }} {{ $order->address->postal_code }} {{ $order->address->country }}
            </div>

            @if ($order->delivery_date || $order->delivery_tracking_number)
                <div class="order-delivery-info">
                    <h4>Delivery Tracking</h4>
                    @if ($order->delivery_date)
                        <p><strong>Expected delivery:</strong> {{ $order->delivery_date->format('F d, Y') }}</p>
                    @endif
                    @if ($order->delivery_tracking_number)
                        <p><strong>Tracking:</strong> {{ $order->delivery_carrier ? $order->delivery_carrier . ' — ' : '' }}{{ $order->delivery_tracking_number }}</p>
                    @endif
                </div>
            @endif

            <div class="order-items">
                <h4>Items</h4>
                <table class="order-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr>
                                <td>
                                    <div class="order-product">
                                        @if ($item->product->image)
                                            <img src="{{ asset('storage/' . $item->product->image) }}" alt="" class="order-thumb">
                                        @else
                                            <div class="order-no-img">—</div>
                                        @endif
                                        {{ $item->product->name }}
                                    </div>
                                </td>
                                <td>${{ number_format($item->price, 2) }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>${{ number_format($item->quantity * $item->price, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="order-total">
                <strong>Total: ${{ number_format($order->total, 2) }}</strong>
            </div>
        </div>

        <div class="order-actions">
            <a href="{{ route('profile.index') }}" class="btn btn-secondary">My Profile</a>
            <a href="{{ route('dashboard') }}" class="btn btn-primary">Continue Shopping</a>
        </div>
    </div>
</div>
