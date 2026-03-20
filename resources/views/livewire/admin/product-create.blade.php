<div>
    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    <div class="form-header">
        <h2>Add New Product</h2>
        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Back to List</a>
    </div>

    <form wire:submit.prevent="save" class="product-form">
        <div class="form-group">
            <label for="name">Product Name <span class="required">*</span></label>
            <input type="text" id="name" wire:model="name" class="form-control @error('name') is-invalid @enderror" placeholder="Enter product name">
            @error('name') <span class="error-message">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" wire:model="description" class="form-control @error('description') is-invalid @enderror" rows="4" placeholder="Enter product description"></textarea>
            @error('description') <span class="error-message">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="category_id">Category</label>
            <select id="category_id" wire:model="category_id" class="form-control">
                <option value="">— Select category —</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="price">Price <span class="required">*</span></label>
                <input type="number" id="price" wire:model="price" step="0.01" min="0" class="form-control @error('price') is-invalid @enderror" placeholder="0.00">
                @error('price') <span class="error-message">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="stock_quantity">Stock Quantity <span class="required">*</span></label>
                <input type="number" id="stock_quantity" wire:model="stock_quantity" min="0" class="form-control @error('stock_quantity') is-invalid @enderror" placeholder="0">
                @error('stock_quantity') <span class="error-message">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="form-group">
            <label>Variants (Size, Color)</label>
            @foreach ($variants as $i => $v)
                <div class="d-flex gap-2 mb-2 align-items-end">
                    <input type="text" wire:model="variants.{{ $i }}.size" class="form-control form-control-sm" placeholder="Size" style="width:100px">
                    <input type="text" wire:model="variants.{{ $i }}.color" class="form-control form-control-sm" placeholder="Color" style="width:100px">
                    <input type="number" wire:model="variants.{{ $i }}.price" step="0.01" class="form-control form-control-sm" placeholder="Price" style="width:90px">
                    <input type="number" wire:model="variants.{{ $i }}.stock_quantity" min="0" class="form-control form-control-sm" placeholder="Stock" style="width:80px">
                    <input type="text" wire:model="variants.{{ $i }}.sku" class="form-control form-control-sm" placeholder="SKU" style="width:100px">
                    <button type="button" wire:click="removeVariant({{ $i }})" class="btn btn-sm btn-outline-danger">×</button>
                </div>
            @endforeach
            <button type="button" wire:click="addVariant" class="btn btn-sm btn-outline-secondary">+ Add Variant</button>
        </div>

        <div class="form-group">
            <label for="image">Product Image</label>
            <input type="file" id="image" wire:model="image" class="form-control @error('image') is-invalid @enderror" accept="image/*">
            @error('image') <span class="error-message">{{ $message }}</span> @enderror
            @if ($image)
                <div class="image-preview">
                    <p>Preview: Uploading...</p>
                </div>
            @endif
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Product</button>
            <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
