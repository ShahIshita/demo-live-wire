<div>
    <div class="cart-header">
        <h2>Your Cart</h2>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">Continue Shopping</a>
    </div>

    @if ($cartItems->isEmpty())
        <div class="empty-cart">
            <p>Your cart is empty.</p>
            <a href="{{ route('dashboard') }}" class="btn btn-primary">Browse Products</a>
        </div>
    @else
        <div class="cart-table">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cartItems as $item)
                        <tr>
                            <td>
                                <div class="cart-product">
                                    @if ($item->product->image)
                                        <img src="{{ asset('storage/' . $item->product->image) }}" alt="{{ $item->product->name }}" class="cart-thumb">
                                    @else
                                        <div class="cart-no-img">—</div>
                                    @endif
                                    <span>{{ $item->product->name }}</span>
                                </div>
                            </td>
                            <td>${{ number_format($item->product->price, 2) }}</td>
                            <td>
                                <div class="qty-controls">
                                    <button wire:click="decrementQuantity({{ $item->id }})" class="qty-btn">−</button>
                                    <span class="qty-value">{{ $item->quantity }}</span>
                                    <button wire:click="incrementQuantity({{ $item->id }})" class="qty-btn">+</button>
                                </div>
                            </td>
                            <td>${{ number_format($item->quantity * $item->product->price, 2) }}</td>
                            <td>
                                <button wire:click="removeItem({{ $item->id }})" 
                                        onclick="return confirm('Remove this item?')"
                                        class="btn btn-remove">Remove</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="cart-total">
            <strong>Total: ${{ number_format($cartTotal, 2) }}</strong>
        </div>

        <div class="cart-actions" style="margin-top: 24px;">
            <a href="{{ route('addresses.index') }}" class="btn btn-secondary">Manage Addresses</a>
            <a href="{{ route('checkout.index') }}" class="btn btn-primary">Proceed to Checkout</a>
        </div>
    @endif
</div>
