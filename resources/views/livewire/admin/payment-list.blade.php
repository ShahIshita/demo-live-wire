<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">Payment Transactions</h5>
        <select wire:model="statusFilter" class="form-select form-select-sm" style="width: 150px;">
            <option value="">All statuses</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="failed">Failed</option>
            <option value="refunded">Refunded</option>
        </select>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <p class="p-3 text-muted small mb-0">Payments are stored with orders. Stripe/Razorpay integration placeholder.</p>
            @if ($orders->isEmpty())
                <p class="p-4 text-muted mb-0">No payment records yet.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Gateway</th>
                                <th>Date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                <tr>
                                    <td><a href="{{ route('admin.orders.show', $order->id) }}">#{{ $order->id }}</a></td>
                                    <td>{{ $order->user->name ?? '—' }}</td>
                                    <td>{{ \App\Setting::get('currency_symbol', '$') }}{{ number_format($order->total, 2) }}</td>
                                    <td><span class="badge bg-{{ ($order->payment_status ?? 'pending') === 'paid' ? 'success' : (($order->payment_status ?? '') === 'failed' ? 'danger' : 'warning') }}">{{ ucfirst($order->payment_status ?? 'pending') }}</span></td>
                                    <td>{{ $order->stripe_payment_intent_id ? 'Stripe' : '—' }}</td>
                                    <td>{{ $order->created_at->format('M d, Y H:i') }}</td>
                                    <td><a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-outline-primary">View Order</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top">{{ $orders->links() }}</div>
            @endif
        </div>
    </div>
</div>
