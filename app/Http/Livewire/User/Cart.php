<?php

namespace App\Http\Livewire\User;

use App\CartItem;
use Livewire\Component;

class Cart extends Component
{
    public function updateQuantity($cartItemId, $quantity)
    {
        $cartItem = CartItem::where('id', $cartItemId)
            ->whereHas('cart', fn ($q) => $q->where('user_id', auth()->id()))
            ->firstOrFail();

        $quantity = max(1, (int) $quantity);
        $cartItem->update(['quantity' => $quantity]);

        session()->flash('message', 'Cart updated.');
    }

    public function incrementQuantity($cartItemId)
    {
        $cartItem = CartItem::where('id', $cartItemId)
            ->whereHas('cart', fn ($q) => $q->where('user_id', auth()->id()))
            ->firstOrFail();

        $cartItem->increment('quantity');
        session()->flash('message', 'Cart updated.');
    }

    public function decrementQuantity($cartItemId)
    {
        $cartItem = CartItem::where('id', $cartItemId)
            ->whereHas('cart', fn ($q) => $q->where('user_id', auth()->id()))
            ->firstOrFail();

        if ($cartItem->quantity > 1) {
            $cartItem->decrement('quantity');
        }
        session()->flash('message', 'Cart updated.');
    }

    public function removeItem($cartItemId)
    {
        $cartItem = CartItem::where('id', $cartItemId)
            ->whereHas('cart', fn ($q) => $q->where('user_id', auth()->id()))
            ->firstOrFail();

        $cartItem->delete();
        session()->flash('message', 'Item removed from cart.');
    }

    public function render()
    {
        $cart = auth()->user()->cart;
        $cartItems = $cart ? $cart->items()->with('product')->get() : collect();
        $cartTotal = $cartItems->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });

        return view('livewire.user.cart', [
            'cartItems' => $cartItems,
            'cartTotal' => $cartTotal,
        ]);
    }
}
