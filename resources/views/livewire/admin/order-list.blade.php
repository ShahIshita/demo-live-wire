<div>
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h5 class="mb-0">Orders</h5>
        <div class="d-flex gap-2 flex-wrap">
            <input type="search" wire:model.debounce.300ms="search" class="form-control form-control-sm" style="width: 160px;" placeholder="Search...">
            <select wire:model="statusFilter" class="form-select form-select-sm" style="width: 140px;">
                <option value="">All statuses</option>
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="shipped">Shipped</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            @if ($orders->isEmpty())
                <p class="p-4 text-muted mb-0">No orders yet.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Delivery</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                <tr>
                                    <td><a href="{{ route('admin.orders.show', $order->id) }}">#{{ $order->id }}</a></td>
                                    <td>{{ $order->user->name }}<br><small class="text-muted">{{ $order->user->email }}</small></td>
                                    <td>{{ \App\Setting::get('currency_symbol', '$') }}{{ number_format($order->total, 2) }}</td>
                                    <td><span class="badge bg-secondary">{{ ucfirst($order->status) }}</span></td>
                                    <td><span class="badge bg-{{ ($order->payment_status ?? 'pending') === 'paid' ? 'success' : (($order->payment_status ?? '') === 'failed' ? 'danger' : 'warning') }}">{{ ucfirst($order->payment_status ?? 'pending') }}</span></td>
                                    <td>
                                        @if ($order->delivery_tracking_number)
                                            {{ $order->delivery_carrier ? $order->delivery_carrier . ': ' : '' }}{{ $order->delivery_tracking_number }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if ($editingOrderId === $order->id)
                                            <div class="border rounded p-2 bg-light" style="min-width: 220px;">
                                                <div class="mb-2">
                                                    <label class="form-label small mb-0">Status</label>
                                                    <select wire:model="status" class="form-select form-select-sm">
                                                        <option value="pending">Pending</option>
                                                        <option value="processing">Processing</option>
                                                        <option value="shipped">Shipped</option>
                                                        <option value="delivered">Delivered</option>
                                                        <option value="cancelled">Cancelled</option>
                                                    </select>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small mb-0">Payment</label>
                                                    <select wire:model="payment_status" class="form-select form-select-sm">
                                                        <option value="pending">Pending</option>
                                                        <option value="paid">Paid</option>
                                                        <option value="failed">Failed</option>
                                                        <option value="refunded">Refunded</option>
                                                    </select>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small mb-0">Delivery Date</label>
                                                    <input type="date" wire:model="delivery_date" class="form-control form-control-sm">
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small mb-0">Carrier</label>
                                                    <select wire:model="delivery_carrier" class="form-select form-select-sm">
                                                        <option value="">—</option>
                                                        <option value="FedEx">FedEx</option>
                                                        <option value="UPS">UPS</option>
                                                        <option value="DHL">DHL</option>
                                                        <option value="USPS">USPS</option>
                                                        <option value="Other">Other</option>
                                                    </select>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small mb-0">Tracking</label>
                                                    <input type="text" wire:model="delivery_tracking_number" class="form-control form-control-sm" placeholder="Tracking #">
                                                </div>
                                                <div class="d-flex gap-1">
                                                    <button type="button" wire:click="saveOrder" class="btn btn-sm btn-primary">Save</button>
                                                    <button type="button" wire:click="cancelEdit" class="btn btn-sm btn-secondary">Cancel</button>
                                                </div>
                                            </div>
                                        @else
                                            <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                            <button type="button" wire:click="editOrder({{ $order->id }})" class="btn btn-sm btn-outline-secondary">Edit</button>
                                        @endif
                                    </td>
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
