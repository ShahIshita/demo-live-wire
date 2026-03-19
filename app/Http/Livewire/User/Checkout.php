<?php

namespace App\Http\Livewire\User;

use App\Address;
use Livewire\Component;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class Checkout extends Component
{
    public $step = 1;
    public $selectedAddressId = null;
    public $clientSecret = null;
    public $paymentIntentId = null;
    public $paymentError = null;

    public function mount()
    {
        $default = auth()->user()->addresses()->where('is_default', true)->first();
        if ($default) {
            $this->selectedAddressId = $default->id;
        }
    }

    public function nextStep()
    {
        if ($this->step === 1 && !$this->selectedAddressId) {
            session()->flash('message', 'Please select a delivery address.');
            return;
        }
        if ($this->step === 1) {
            $this->step = 2;
        } elseif ($this->step === 2) {
            $this->step = 3;
            $this->createPaymentIntent();
        }
    }

    public function prevStep()
    {
        if ($this->step > 1) {
            $this->step--;
            $this->paymentError = null;
            $this->clientSecret = null;
        }
    }

    public function selectAddress($id)
    {
        $this->selectedAddressId = $id;
    }

    protected function createPaymentIntent()
    {
        $this->paymentError = null;
        $this->clientSecret = null;

        $user = auth()->user();
        $address = Address::where('user_id', $user->id)->find($this->selectedAddressId);
        if (!$address) {
            $this->paymentError = 'Invalid address.';
            return;
        }

        $cart = $user->cart;
        $cartItems = $cart ? $cart->items()->with('product')->get() : collect();
        $total = $cartItems->sum(function ($i) {
            return $i->quantity * $i->product->price;
        });

        if ($total < 0.5) {
            $this->paymentError = 'Minimum amount is $0.50.';
            return;
        }

        $amountCents = (int) round($total * 100);

        $secret = config('services.stripe.secret') ?: env('STRIPE_SECRET');
        if (empty($secret)) {
            $this->paymentError = 'Stripe is not configured. Add STRIPE_SECRET to .env and run: php artisan config:clear';
            return;
        }
        Stripe::setApiKey($secret);

        try {
            $intent = PaymentIntent::create([
                'amount' => $amountCents,
                'currency' => 'usd',
                'metadata' => [
                    'user_id' => $user->id,
                    'address_id' => $address->id,
                ],
            ]);
            $this->clientSecret = $intent->client_secret;
            $this->paymentIntentId = $intent->id;
        } catch (ApiErrorException $e) {
            $this->paymentError = $e->getMessage();
        }
    }

    public function render()
    {
        $cart = auth()->user()->cart;
        $cartItems = $cart ? $cart->items()->with('product')->get() : collect();
        $cartTotal = $cartItems->sum(function ($i) {
            return $i->quantity * $i->product->price;
        });
        $addresses = auth()->user()->addresses()->orderBy('is_default', 'desc')->get();
        $selectedAddress = $this->selectedAddressId
            ? $addresses->firstWhere('id', $this->selectedAddressId)
            : null;

        return view('livewire.user.checkout', [
            'cartItems' => $cartItems,
            'cartTotal' => $cartTotal,
            'addresses' => $addresses,
            'selectedAddress' => $selectedAddress,
        ]);
    }
}
