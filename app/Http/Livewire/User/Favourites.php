<?php

namespace App\Http\Livewire\User;

use App\Favourite;
use App\Product;
use Livewire\Component;

class Favourites extends Component
{
    public function removeFavourite($productId)
    {
        auth()->user()->favourites()->where('product_id', $productId)->delete();
        session()->flash('message', 'Removed from favourites.');
    }

    public function addToCart($productId)
    {
        $user = auth()->user();
        $product = Product::findOrFail($productId);

        $cart = $user->cart ?? \App\Cart::create(['user_id' => $user->id]);

        $cartItem = $cart->items()->where('product_id', $productId)->first();

        if ($cartItem) {
            $cartItem->increment('quantity');
        } else {
            \App\CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $productId,
                'quantity' => 1,
            ]);
        }

        session()->flash('message', "{$product->name} added to cart.");
    }

    public function render()
    {
        $products = auth()->user()->favouriteProducts;

        return view('livewire.user.favourites', [
            'products' => $products,
        ]);
    }
}
