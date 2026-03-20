<div>
    <div class="mb-3">
        <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary">← Back to Orders</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">Order #{{ $order->id }}</div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Status:</strong> <span class="badge bg-secondary">{{ ucfirst($order->status) }}</span><br>
                            <strong>Payment:</strong> <span class="badge bg-{{ $order->payment_status === 'paid' ? 'success' : ($order->payment_status === 'failed' ? 'danger' : 'warning') }}">{{ ucfirst($order->payment_status ?? 'pending') }}</span>
                        </div>
                        <div class="col-md-6">
                            <strong>Date:</strong> {{ $order->created_at->format('M d, Y H:i') }}<br>
                            <strong>Total:</strong> {{ \App\Setting::get('currency_symbol', '$') }}{{ number_format($order->total, 2) }}
                        </div>
                    </div>

                    <h6 class="mt-4">Products</h6>
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td>{{ $item->product->name ?? 'N/A' }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ \App\Setting::get('currency_symbol', '$') }}{{ number_format($item->price, 2) }}</td>
                                    <td>{{ \App\Setting::get('currency_symbol', '$') }}{{ number_format($item->quantity * $item->price, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">Customer</div>
                <div class="card-body">
                    <strong>{{ $order->user->name }}</strong><br>
                    {{ $order->user->email }}
                </div>
            </div>
            @if ($order->address)
            <div class="card mt-3">
                <div class="card-header">Shipping Address</div>
                <div class="card-body">
                    {{ $order->address->address_line1 }}<br>
                    @if ($order->address->address_line2){{ $order->address->address_line2 }}<br>@endif
                    {{ $order->address->city }}, {{ $order->address->state }} {{ $order->address->postal_code }}<br>
                    {{ $order->address->country }}
                </div>
            </div>
            @endif
            @if ($order->delivery_tracking_number)
            <div class="card mt-3">
                <div class="card-header">Delivery</div>
                <div class="card-body">
                    <strong>Carrier:</strong> {{ $order->delivery_carrier ?? '—' }}<br>
                    <strong>Tracking:</strong> {{ $order->delivery_tracking_number }}<br>
                    <strong>Date:</strong> {{ $order->delivery_date ? $order->delivery_date->format('M d, Y') : '—' }}
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
