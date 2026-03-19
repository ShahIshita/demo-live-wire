<div>
    @if (session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    <div class="product-header">
        <h2>Orders Management</h2>
    </div>

    <div class="product-table-container">
        @if ($orders->isEmpty())
            <p class="empty-state">No orders yet.</p>
        @else
            <table class="product-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Delivery Date</th>
                        <th>Tracking</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td>{{ $order->id }}</td>
                            <td>{{ $order->user->name }}<br><small>{{ $order->user->email }}</small></td>
                            <td>${{ number_format($order->total, 2) }}</td>
                            <td>{{ ucfirst($order->status) }}</td>
                            <td>{{ $order->delivery_date ? $order->delivery_date->format('M d, Y') : '—' }}</td>
                            <td>
                                @if ($order->delivery_tracking_number)
                                    {{ $order->delivery_carrier ? $order->delivery_carrier . ': ' : '' }}{{ $order->delivery_tracking_number }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($editingOrderId === $order->id)
                                    <div class="delivery-edit-form">
                                        <div class="form-group">
                                            <label>Delivery Date</label>
                                            <input type="date" wire:model="delivery_date" class="form-control">
                                        </div>
                                        <div class="form-group">
                                            <label>Carrier</label>
                                            <select wire:model="delivery_carrier" class="form-control">
                                                <option value="">Select carrier</option>
                                                <option value="FedEx">FedEx</option>
                                                <option value="UPS">UPS</option>
                                                <option value="DHL">DHL</option>
                                                <option value="USPS">USPS</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Tracking Number</label>
                                            <input type="text" wire:model="delivery_tracking_number" class="form-control" placeholder="e.g. 1Z999AA10123456784">
                                        </div>
                                        <div class="form-actions">
                                            <button type="button" wire:click="saveDelivery" class="btn btn-sm btn-primary">Save</button>
                                            <button type="button" wire:click="cancelEdit" class="btn btn-sm btn-secondary">Cancel</button>
                                        </div>
                                    </div>
                                @else
                                    <button type="button" wire:click="editDelivery({{ $order->id }})" class="btn btn-sm btn-edit">Manage Delivery</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
