<?php

namespace App\Http\Livewire\User;

use App\Cart;
use App\CartItem;
use App\Favourite;
use App\Product;
use Livewire\Component;

class ProductGrid extends Component
{
    public function addToCart($productId)
    {
        $user = auth()->user();
        $product = Product::findOrFail($productId);

        $cart = $user->cart ?? Cart::create(['user_id' => $user->id]);

        $cartItem = $cart->items()->where('product_id', $productId)->first();

        if ($cartItem) {
            $cartItem->increment('quantity');
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $productId,
                'quantity' => 1,
            ]);
        }

        session()->flash('message', "{$product->name} added to cart.");
    }

    public function addToFavourite($productId)
    {
        $user = auth()->user();

        if ($user->favourites()->where('product_id', $productId)->exists()) {
            $user->favourites()->where('product_id', $productId)->delete();
            session()->flash('message', 'Removed from favourites.');
        } else {
            Favourite::create([
                'user_id' => $user->id,
                'product_id' => $productId,
            ]);
            $product = Product::find($productId);
            session()->flash('message', "{$product->name} added to favourites.");
        }
    }

    public function isInCart($productId)
    {
        $cart = auth()->user()->cart;
        if (!$cart) {
            return false;
        }
        return $cart->items()->where('product_id', $productId)->exists();
    }

    public function isFavourite($productId)
    {
        return auth()->user()->favourites()->where('product_id', $productId)->exists();
    }

    public function render()
    {
        return view('livewire.user.product-grid', [
            'products' => Product::where('stock_quantity', '>', 0)->orderBy('created_at', 'desc')->get(),
        ]);
    }
}
