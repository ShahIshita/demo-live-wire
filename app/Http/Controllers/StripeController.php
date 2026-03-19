<?php

namespace App\Http\Controllers;

use App\Address;
use App\CartItem;
use App\Order;
use App\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripeController extends Controller
{
    protected function ensureStripeKey()
    {
        $secret = config('services.stripe.secret') ?: env('STRIPE_SECRET');
        if (empty($secret)) {
            abort(500, 'Stripe not configured. Add STRIPE_SECRET to .env and run: php artisan config:clear');
        }
        Stripe::setApiKey($secret);
    }

    /**
     * Create a PaymentIntent for the checkout amount.
     */
    public function createPaymentIntent(Request $request)
    {
        $this->ensureStripeKey();
        $request->validate([
            'amount' => 'required|numeric|min:50', // Stripe minimum is 50 cents
            'address_id' => 'required|exists:addresses,id',
        ]);

        $user = $request->user();
        $address = Address::where('user_id', $user->id)->findOrFail($request->address_id);

        $cart = $user->cart;
        if (!$cart || $cart->items()->count() === 0) {
            return response()->json(['error' => 'Cart is empty'], 400);
        }

        $amountCents = (int) round($request->amount * 100);

        try {
            $intent = PaymentIntent::create([
                'amount' => $amountCents,
                'currency' => 'usd',
                'metadata' => [
                    'user_id' => $user->id,
                    'address_id' => $address->id,
                ],
            ]);

            return response()->json([
                'clientSecret' => $intent->client_secret,
                'paymentIntentId' => $intent->id,
            ]);
        } catch (ApiErrorException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Create order after successful payment. Called from frontend after Stripe confirms.
     */
    public function confirmOrder(Request $request)
    {
        $this->ensureStripeKey();
        $request->validate([
            'payment_intent_id' => 'required|string',
            'address_id' => 'required|exists:addresses,id',
        ]);

        $user = $request->user();
        $address = Address::where('user_id', $user->id)->findOrFail($request->address_id);

        $cart = $user->cart;
        if (!$cart || $cart->items()->count() === 0) {
            return response()->json(['error' => 'Cart is empty'], 400);
        }

        try {
            $intent = PaymentIntent::retrieve($request->payment_intent_id);
            if ($intent->status !== 'succeeded') {
                return response()->json(['error' => 'Payment not completed'], 400);
            }
        } catch (ApiErrorException $e) {
            return response()->json(['error' => 'Invalid payment'], 400);
        }

        if (Order::where('stripe_payment_intent_id', $request->payment_intent_id)->exists()) {
            return response()->json(['error' => 'Order already placed', 'order_id' => Order::where('stripe_payment_intent_id', $request->payment_intent_id)->first()->id], 400);
        }

        DB::beginTransaction();
        try {
            $cartItems = $cart->items()->with('product')->get();
            $subtotal = $cartItems->sum(function ($i) {
                return $i->quantity * $i->product->price;
            });
            $total = $subtotal;

            $order = Order::create([
                'user_id' => $user->id,
                'address_id' => $address->id,
                'status' => 'placed',
                'subtotal' => $subtotal,
                'total' => $total,
                'stripe_payment_intent_id' => $request->payment_intent_id,
                'stripe_payment_status' => 'succeeded',
            ]);

            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                ]);
                $item->product->decrement('stock_quantity', $item->quantity);
            }

            $cart->items()->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'redirect' => route('orders.show', $order->id),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create order: ' . $e->getMessage()], 500);
        }
    }
}
