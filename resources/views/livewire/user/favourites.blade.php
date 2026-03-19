<div>
    <div class="fav-header">
        <h2>My Favourites</h2>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">Back to Products</a>
    </div>

    @if ($products->isEmpty())
        <div class="empty-fav">
            <p>You have no favourite products yet.</p>
            <a href="{{ route('dashboard') }}" class="btn btn-primary">Browse Products</a>
        </div>
    @else
        <div class="product-grid">
            @foreach ($products as $product)
                <div class="product-card">
                    <div class="product-card-image">
                        @if ($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}">
                        @else
                            <div class="no-image-placeholder">No Image</div>
                        @endif
                    </div>
                    <div class="product-card-body">
                        <h3 class="product-name">{{ $product->name }}</h3>
                        <p class="product-price">${{ number_format($product->price, 2) }}</p>
                        <div class="product-actions">
                            <button wire:click="addToCart({{ $product->id }})" class="btn btn-add-cart">Add to Cart</button>
                            <button wire:click="removeFavourite({{ $product->id }})" class="btn btn-remove-fav">Remove</button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
