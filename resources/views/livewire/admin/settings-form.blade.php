<div>
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form wire:submit.prevent="save">
        <div class="card mb-4">
            <div class="card-header">General</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Site Name</label>
                        <input type="text" wire:model="site_name" class="form-control">
                        @error('site_name') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Site Email</label>
                        <input type="email" wire:model="site_email" class="form-control">
                        @error('site_email') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Logo URL</label>
                        <input type="text" wire:model="site_logo" class="form-control" placeholder="https://...">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Tax & Currency</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Currency</label>
                        <input type="text" wire:model="currency" class="form-control" placeholder="USD">
                        @error('currency') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Currency Symbol</label>
                        <input type="text" wire:model="currency_symbol" class="form-control" placeholder="$">
                        @error('currency_symbol') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tax Rate (%)</label>
                        <input type="number" wire:model="tax_rate" step="0.01" min="0" class="form-control">
                        @error('tax_rate') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tax Label</label>
                        <input type="text" wire:model="tax_label" class="form-control" placeholder="Tax">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</div>
