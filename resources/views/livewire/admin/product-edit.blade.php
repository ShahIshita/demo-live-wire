<div>
    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    <div class="form-header">
        <h2>Edit Product</h2>
        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Back to List</a>
    </div>

    <form wire:submit.prevent="update" class="product-form">
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
            <label for="image">Product Image</label>
            @if ($product->image)
                <div class="current-image">
                    <p>Current image:</p>
                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="product-thumb">
                </div>
            @endif
            <input type="file" id="image" wire:model="image" class="form-control @error('image') is-invalid @enderror" accept="image/*">
            <small class="form-hint">Leave empty to keep current image</small>
            @error('image') <span class="error-message">{{ $message }}</span> @enderror
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Product</button>
            <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
