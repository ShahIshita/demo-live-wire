<div>
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="mb-4">
        <h5 class="mb-0">Shipping Methods & Charges</h5>
        <p class="text-muted small mb-0">Define delivery methods and shipping charges.</p>
    </div>

    <div class="card">
        <div class="card-body p-0">
            @if ($methods->isEmpty())
                <p class="p-4 text-muted mb-0">No shipping methods. Run: <code>php artisan db:seed --class=ShippingMethodSeeder</code></p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Charge</th>
                                <th>Free over</th>
                                <th>Est. Days</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($methods as $m)
                                <tr>
                                    <td>
                                        @if ($editingId === $m->id)
                                            <input type="text" wire:model="name" class="form-control form-control-sm">
                                        @else
                                            <strong>{{ $m->name }}</strong>
                                            @if ($m->description)<br><small class="text-muted">{{ $m->description }}</small>@endif
                                        @endif
                                    </td>
                                    <td>
                                        @if ($editingId === $m->id)
                                            <input type="number" wire:model="charge" step="0.01" class="form-control form-control-sm" style="width:90px">
                                        @else
                                            {{ \App\Setting::get('currency_symbol', '$') }}{{ number_format($m->charge, 2) }}
                                        @endif
                                    </td>
                                    <td>
                                        @if ($editingId === $m->id)
                                            <input type="number" wire:model="min_order_amount" class="form-control form-control-sm" style="width:80px">
                                        @else
                                            {{ $m->min_order_amount ? \App\Setting::get('currency_symbol', '$') . $m->min_order_amount : '—' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if ($editingId === $m->id)
                                            <input type="number" wire:model="estimated_days" class="form-control form-control-sm" style="width:70px" placeholder="Days">
                                        @else
                                            {{ $m->estimated_days ? $m->estimated_days . ' days' : '—' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if ($editingId === $m->id)
                                            <div class="form-check">
                                                <input type="checkbox" wire:model="is_active" class="form-check-input" id="active-{{ $m->id }}">
                                                <label class="form-check-label" for="active-{{ $m->id }}">Active</label>
                                            </div>
                                        @else
                                            @if ($m->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @if ($editingId === $m->id)
                                            <button type="button" wire:click="save" class="btn btn-sm btn-primary">Save</button>
                                            <button type="button" wire:click="cancelEdit" class="btn btn-sm btn-secondary">Cancel</button>
                                        @else
                                            <button type="button" wire:click="edit({{ $m->id }})" class="btn btn-sm btn-outline-primary">Edit</button>
                                            <button type="button" wire:click="toggleActive({{ $m->id }})" class="btn btn-sm btn-outline-{{ $m->is_active ? 'danger' : 'success' }}">{{ $m->is_active ? 'Disable' : 'Enable' }}</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
