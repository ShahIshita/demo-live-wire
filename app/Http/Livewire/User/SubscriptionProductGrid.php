<?php

namespace App\Http\Livewire\User;

use App\Cart;
use App\CartItem;
use App\Favourite;
use App\Product;
use App\SubscriptionPlan;
use App\UserSubscription;
use Livewire\Component;

class SubscriptionProductGrid extends Component
{
    /** How many products appear on the Products tab (subscription module). */
    public const FEATURED_LIMIT = 4;

    protected function subscribedProductIds(): array
    {
        $user = auth()->user();

        return UserSubscription::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'trialing', 'past_due', 'unpaid'])
            ->where('cancel_at_period_end', false)
            ->pluck('product_id')
            ->unique()
            ->values()
            ->all();
    }

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
        $subscribedProductIds = $this->subscribedProductIds();

        $products = Product::where('stock_quantity', '>', 0)
            ->orderBy('id')
            ->limit(self::FEATURED_LIMIT)
            ->get();

        $plansByProduct = SubscriptionPlan::query()
            ->whereIn('product_id', $products->pluck('id'))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('product_id');

        $plansByProductForJs = [];
        foreach ($plansByProduct as $productId => $plans) {
            $plansByProductForJs[(int) $productId] = $plans->map(function (SubscriptionPlan $p) {
                return [
                    'id' => $p->id,
                    'title' => $p->title,
                ];
            })->values()->all();
        }

        return view('livewire.user.subscription-product-grid', [
            'products' => $products,
            'subscribedProductIds' => $subscribedProductIds,
            'plansByProductForJs' => $plansByProductForJs,
        ]);
    }
}
