<div>
    <div class="products-header">
        <h2>Our Products</h2>
        @unless (auth()->user()->is_admin ?? false)
            <div class="header-links">
                <a href="{{ route('cart.index') }}" class="btn btn-cart">
                    Cart ({{ auth()->user()->cart ? auth()->user()->cart->items->sum('quantity') : 0 }})
                </a>
                <a href="{{ route('favourites.index') }}" class="btn btn-favourite">My Favourites</a>
            </div>
        @endunless
    </div>

    <div class="product-grid">
        @forelse ($products as $product)
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
                    <p class="product-desc">{{ auth()->user()->is_admin ? $product->description : Str::limit($product->description, 80) }}</p>
                    <p class="product-price">${{ number_format($product->price, 2) }}</p>
                    <p class="product-stock">In stock: {{ $product->stock_quantity }}</p>
                    @unless (auth()->user()->is_admin ?? false)
                        <div class="product-actions">
                            <button wire:click="addToCart({{ $product->id }})" class="btn btn-add-cart">
                                {{ $this->isInCart($product->id) ? 'In Cart ✓' : 'Add to Cart' }}
                            </button>
                            <button wire:click="addToFavourite({{ $product->id }})" class="btn btn-fav {{ $this->isFavourite($product->id) ? 'is-favourite' : '' }}">
                                {{ $this->isFavourite($product->id) ? '♥ Favourited' : '♡ Favourite' }}
                            </button>
                        </div>
                    @endunless
                </div>
            </div>
        @empty
            <p class="empty-products">No products available at the moment.</p>
        @endforelse
    </div>
</div>
