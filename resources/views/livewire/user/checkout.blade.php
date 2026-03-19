<div>
    <div class="checkout-header">
        <h2>Checkout</h2>
        <a href="{{ route('cart.index') }}" class="btn btn-secondary">Back to Cart</a>
    </div>

    @if ($cartItems->isEmpty())
        <div class="empty-checkout">
            <p>Your cart is empty. Add products before checkout.</p>
            <a href="{{ route('dashboard') }}" class="btn btn-primary">Browse Products</a>
        </div>
    @else
        <div class="checkout-steps">
            <div class="step-indicator">
                <span class="step {{ $step >= 1 ? 'active' : '' }}">1. Address</span>
                <span class="step {{ $step >= 2 ? 'active' : '' }}">2. Review</span>
                <span class="step {{ $step >= 3 ? 'active' : '' }}">3. Payment</span>
            </div>

            @if ($step === 1)
                <div class="checkout-step card">
                    <h3>Select Delivery Address</h3>
                    @if ($addresses->isEmpty())
                        <p>No addresses saved. <a href="{{ route('profile.index') }}?tab=address">Add an address in Profile</a> to continue.</p>
                    @else
                        <div class="address-select-list">
                            @foreach ($addresses as $addr)
                                <div class="address-option {{ $selectedAddressId == $addr->id ? 'selected' : '' }}" wire:click="selectAddress({{ $addr->id }})">
                                    @if ($addr->is_default)<span class="default-badge">Default</span>@endif
                                    @if ($addr->label)<strong>{{ $addr->label }}</strong><br>@endif
                                    {{ $addr->address_line1 }}{{ $addr->address_line2 ? ', ' . $addr->address_line2 : '' }}<br>
                                    {{ $addr->city }}{{ $addr->state ? ', ' . $addr->state : '' }} {{ $addr->postal_code }} {{ $addr->country }}
                                </div>
                            @endforeach
                        </div>
                        <a href="{{ route('profile.index') }}?tab=address" class="btn btn-secondary" style="margin-top: 12px;">Manage Addresses</a>
                    @endif
                    <div class="step-actions">
                        <button type="button" wire:click="nextStep" class="btn btn-primary" {{ $addresses->isEmpty() ? 'disabled' : '' }}>Continue</button>
                    </div>
                </div>
            @endif

            @if ($step === 2)
                <div class="checkout-step card">
                    <h3>Order Summary</h3>
                    @if ($selectedAddress)
                        <div class="selected-address-box">
                            <strong>Delivery to:</strong><br>
                            {{ $selectedAddress->address_line1 }}{{ $selectedAddress->address_line2 ? ', ' . $selectedAddress->address_line2 : '' }}<br>
                            {{ $selectedAddress->city }}, {{ $selectedAddress->state }} {{ $selectedAddress->postal_code }} {{ $selectedAddress->country }}
                        </div>
                    @endif
                    <div class="checkout-table">
                        <table>
                            <thead>
                                <tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($cartItems as $item)
                                    <tr>
                                        <td>
                                            <div class="checkout-product">
                                                @if ($item->product->image)
                                                    <img src="{{ asset('storage/' . $item->product->image) }}" alt="" class="checkout-thumb">
                                                @else
                                                    <div class="checkout-no-img">—</div>
                                                @endif
                                                <strong>{{ $item->product->name }}</strong>
                                            </div>
                                        </td>
                                        <td>${{ number_format($item->product->price, 2) }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>${{ number_format($item->quantity * $item->product->price, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="checkout-total"><strong>Total: ${{ number_format($cartTotal, 2) }}</strong></div>
                    <div class="step-actions">
                        <button type="button" wire:click="prevStep" class="btn btn-secondary">Back</button>
                        <button type="button" wire:click="nextStep" class="btn btn-primary">Proceed to Payment</button>
                    </div>
                </div>
            @endif

            @if ($step === 3)
                <div class="checkout-step card">
                    <h3>Payment</h3>
                    @if ($paymentError)
                        <div class="alert alert-danger">{{ $paymentError }}</div>
                        <button type="button" wire:click="prevStep" class="btn btn-secondary">Back</button>
                    @elseif ($clientSecret)
                        <div class="stripe-payment">
                            <div id="card-element"></div>
                            <div id="card-errors" class="payment-errors"></div>
                            <div class="step-actions" style="margin-top: 20px;">
                                <button type="button" wire:click="prevStep" class="btn btn-secondary">Back</button>
                                <button type="button" id="pay-button" class="btn btn-primary">Pay ${{ number_format($cartTotal, 2) }}</button>
                            </div>
                        </div>
                        <script>
                        (function() {
                            var secret = @json($clientSecret);
                            var addrId = @json($selectedAddressId);
                            var intentId = @json($paymentIntentId);
                            var total = @json($cartTotal);
                            var stripeKey = @json(config('services.stripe.key'));
                            if (!secret || !stripeKey) return;
                            var stripe = Stripe(stripeKey);
                            var elements = stripe.elements();
                            var card = elements.create('card');
                            card.mount('#card-element');
                            card.on('change', function(e) {
                                var err = document.getElementById('card-errors');
                                err.textContent = e.error ? e.error.message : '';
                            });
                            document.getElementById('pay-button').onclick = function() {
                                var btn = this;
                                btn.disabled = true;
                                btn.textContent = 'Processing...';
                                stripe.confirmCardPayment(secret, { payment_method: { card: card } })
                                    .then(function(result) {
                                        if (result.error) {
                                            btn.disabled = false;
                                            btn.textContent = 'Pay $' + total.toFixed(2);
                                            document.getElementById('card-errors').textContent = result.error.message;
                                        } else {
                                            fetch('{{ route('stripe.confirm-order') }}', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                                    'Accept': 'application/json'
                                                },
                                                body: JSON.stringify({
                                                    payment_intent_id: result.paymentIntent.id,
                                                    address_id: addrId,
                                                    _token: document.querySelector('meta[name="csrf-token"]').content
                                                })
                                            }).then(function(r) { return r.json(); }).then(function(data) {
                                                if (data.redirect) window.location.href = data.redirect;
                                                else { alert(data.error || 'Order failed'); btn.disabled = false; btn.textContent = 'Pay $' + total.toFixed(2); }
                                            });
                                        }
                                    });
                            };
                        })();
                        </script>
                    @else
                        <p>Loading payment form...</p>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
@endpush
