<?php

namespace App\Http\Livewire\User;

use App\Cart;
use App\CartItem;
use App\Favourite;
use App\Product;
use App\Support\StripeTimeouts;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\Subscription;

class SubscriptionProductGrid extends Component
{
    /** How many products appear on the Products tab (subscription module). */
    public const FEATURED_LIMIT = 4;

    protected function subscribedProductIds(): array
    {
        $user = auth()->user();
        $secret = config('services.stripe.secret') ?: env('STRIPE_SECRET');

        if (empty($user->stripe_customer_id) || empty($secret)) {
            return [];
        }

        $cacheKey = StripeTimeouts::cacheKeySubscribedProductIds($user);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        Stripe::setApiKey($secret);
        StripeTimeouts::apply();

        try {
            $subscriptions = Subscription::all([
                'customer' => $user->stripe_customer_id,
                'status' => 'all',
                'limit' => 100,
            ]);
        } catch (ApiErrorException $e) {
            return [];
        }

        $productIds = [];
        foreach ($subscriptions->data as $subscription) {
            if (in_array($subscription->status, ['active', 'trialing', 'past_due'])) {
                $productId = $subscription->metadata->product_id ?? null;
                if (!empty($productId)) {
                    $productIds[] = (int) $productId;
                }
            }
        }

        $productIds = array_values(array_unique($productIds));
        Cache::put($cacheKey, $productIds, 120);

        return $productIds;
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

        return view('livewire.user.subscription-product-grid', [
            'products' => $products,
            'subscribedProductIds' => $subscribedProductIds,
        ]);
    }
}
