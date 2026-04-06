<?php

namespace App\Http\Controllers;

use App\Address;
use App\Order;
use App\OrderItem;
use App\Support\LocalSubscriptionBilling;
use App\Support\StripeTimeouts;
use App\User;
use App\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeController extends Controller
{
    protected function applySuccessfulPaymentState(Order $order): void
    {
        $order->stripe_payment_status = 'succeeded';
        $order->payment_status = 'paid';

        if ($order->status === 'pending') {
            $order->status = 'processing';
        }
    }

    protected function applyFailedPaymentState(Order $order): void
    {
        $order->stripe_payment_status = 'failed';
        $order->payment_status = 'failed';
    }

    protected function formatStripeAmount($amountInCents): string
    {
        return number_format(((int) $amountInCents) / 100, 2);
    }

    protected function resolveUserFromWebhookObject($object, ?Order $order): ?User
    {
        if ($order && $order->user) {
            return $order->user;
        }

        $metadataUserId = $object->metadata->user_id ?? null;
        if ($metadataUserId) {
            return User::find($metadataUserId);
        }

        return null;
    }

    protected function sendPaymentReceiptEmail(User $user, ?Order $order, $object): void
    {
        $amount = $this->formatStripeAmount($object->amount_received ?? $object->amount ?? 0);
        $intentId = $object->id ?? 'N/A';
        $orderRef = $order ? ('Order #' . $order->id) : 'Order reference unavailable';

        Mail::raw(
            "Hi {$user->name},\n\nYour payment was successful.\n{$orderRef}\nAmount: \${$amount}\nPayment Intent: {$intentId}\n\nThank you for shopping with us.",
            function ($message) use ($user, $order) {
                $subject = $order
                    ? 'Payment Receipt - Order #' . $order->id
                    : 'Payment Receipt';
                $message->to($user->email)->subject($subject);
            }
        );
    }

    protected function sendPaymentFailedEmail(User $user, ?Order $order, $object): void
    {
        $amount = $this->formatStripeAmount($object->amount ?? 0);
        $intentId = $object->id ?? 'N/A';
        $orderRef = $order ? ('Order #' . $order->id) : 'Order reference unavailable';

        Mail::raw(
            "Hi {$user->name},\n\nYour payment attempt failed.\n{$orderRef}\nAmount: \${$amount}\nPayment Intent: {$intentId}\nPlease try again with another payment method.\n\nIf this continues, contact support.",
            function ($message) use ($user, $order) {
                $subject = $order
                    ? 'Payment Failed - Order #' . $order->id
                    : 'Payment Failed';
                $message->to($user->email)->subject($subject);
            }
        );
    }

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

        $existingOrder = Order::where('stripe_payment_intent_id', $request->payment_intent_id)->first();
        if ($existingOrder) {
            $this->applySuccessfulPaymentState($existingOrder);
            $existingOrder->save();

            return response()->json([
                'success' => true,
                'order_id' => $existingOrder->id,
                'redirect' => route('orders.show', $existingOrder->id),
                'message' => 'Order already placed.',
            ]);
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
                'status' => 'pending',
                'subtotal' => $subtotal,
                'total' => $total,
                'stripe_payment_intent_id' => $request->payment_intent_id,
            ]);
            $this->applySuccessfulPaymentState($order);
            $order->save();

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

    /**
     * Handle Stripe webhook events.
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret') ?: env('STRIPE_WEBHOOK_SECRET');

        if (empty($secret)) {
            Log::error('Stripe webhook secret missing.');
            return response()->json(['error' => 'Webhook secret not configured'], 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (UnexpectedValueException $e) {
            Log::warning('Invalid Stripe webhook payload.', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::warning('Invalid Stripe webhook signature.', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            Log::error('Stripe webhook parse error.', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Webhook error'], 500);
        }

        $type = $event->type;
        $object = $event->data->object;

        if (in_array($type, ['payment_intent.succeeded', 'payment_intent.payment_failed'], true)) {
            $intentId = $object->id ?? null;
            if ($intentId) {
                $order = Order::with('user')->where('stripe_payment_intent_id', $intentId)->first();
                if ($order) {
                    $previousPaymentStatus = $order->payment_status;

                    if ($type === 'payment_intent.succeeded') {
                        if ($order->status === 'cancelled') {
                            // keep cancelled status untouched
                            $order->stripe_payment_status = 'succeeded';
                            $order->payment_status = 'paid';
                        } else {
                            $this->applySuccessfulPaymentState($order);
                        }
                    } else {
                        $this->applyFailedPaymentState($order);
                    }
                    $order->save();

                    $user = $this->resolveUserFromWebhookObject($object, $order);
                    if ($user) {
                        if ($type === 'payment_intent.succeeded' && $previousPaymentStatus !== 'paid') {
                            $this->sendPaymentReceiptEmail($user, $order, $object);
                        }
                        if ($type === 'payment_intent.payment_failed' && $previousPaymentStatus !== 'failed') {
                            $this->sendPaymentFailedEmail($user, $order, $object);
                        }
                    }
                } else {
                    $this->handleLocalSubscriptionRenewalWebhook($type, $object);

                    // Fallback for failed shop checkout when order has not been created yet (not subscription renewal).
                    if ($type === 'payment_intent.payment_failed') {
                        $user = $this->resolveUserFromWebhookObject($object, null);
                        if ($user && ($object->metadata->billing_type ?? '') !== LocalSubscriptionBilling::BILLING_TYPE_RENEWAL) {
                            $this->sendPaymentFailedEmail($user, null, $object);
                        }
                    }
                }
            }
        }

        if ($type === 'charge.refunded') {
            $intentId = $object->payment_intent ?? null;
            if ($intentId) {
                $order = Order::where('stripe_payment_intent_id', $intentId)->first();
                if ($order) {
                    $order->payment_status = 'refunded';
                    $order->save();
                }
            }
        }

        return response()->json(['received' => true]);
    }

    /**
     * Local DB subscriptions use one-off PaymentIntents for renewals (no Stripe Subscription objects).
     * If the renewal command succeeds, DB is updated there; this webhook backs up failure state if Stripe notifies async.
     */
    protected function handleLocalSubscriptionRenewalWebhook(string $type, $object): void
    {
        if (($object->metadata->billing_type ?? '') !== LocalSubscriptionBilling::BILLING_TYPE_RENEWAL) {
            return;
        }

        $usId = $object->metadata->user_subscription_id ?? null;
        if (!$usId) {
            return;
        }

        $us = UserSubscription::with('user')->find((int) $usId);
        if (!$us || !$us->user) {
            return;
        }

        if ($type === 'payment_intent.payment_failed' && in_array($us->status, ['active', 'trialing'], true)) {
            $us->update(['status' => 'past_due']);
            StripeTimeouts::forgetUserSubscriptionCaches($us->user);
            Log::info('Subscription renewal payment failed (webhook)', ['user_subscription_id' => $us->id]);
        }
    }
}
