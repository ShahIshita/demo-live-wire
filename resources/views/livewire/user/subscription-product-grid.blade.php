<div>
    <div class="products-header">
        <h2>Featured products ({{ $products->count() }} of {{ \App\Http\Livewire\User\SubscriptionProductGrid::FEATURED_LIMIT }})</h2>
        @unless (auth()->user()->is_admin ?? false)
            <div class="header-links">
                <a href="{{ route('dashboard') }}" class="btn btn-secondary" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3);">All products</a>
                <a href="{{ route('cart.index') }}" class="btn btn-cart">
                    Cart ({{ auth()->user()->cart ? auth()->user()->cart->items->sum('quantity') : 0 }})
                </a>
                <a href="{{ route('favourites.index') }}" class="btn btn-favourite">My Favourites</a>
            </div>
        @endunless
    </div>

    <div class="product-grid">
        @forelse ($products as $product)
            <div class="product-card">
                <div class="product-card-image">
                    @if ($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}">
                    @else
                        <div class="no-image-placeholder">No Image</div>
                    @endif
                </div>
                <div class="product-card-body">
                    <h3 class="product-name">{{ $product->name }}</h3>
                    <p class="product-desc">{{ auth()->user()->is_admin ? $product->description : Str::limit($product->description, 80) }}</p>
                    <p class="product-price">${{ number_format($product->price, 2) }}</p>
                    <p class="product-stock">In stock: {{ $product->stock_quantity }}</p>

                    @unless (auth()->user()->is_admin ?? false)
                        <div class="product-actions">
                            <button wire:click="addToCart({{ $product->id }})" class="btn btn-add-cart">
                                {{ $this->isInCart($product->id) ? 'In Cart ✓' : 'Add to Cart' }}
                            </button>
                            <button wire:click="addToFavourite({{ $product->id }})" class="btn btn-fav {{ $this->isFavourite($product->id) ? 'is-favourite' : '' }}">
                                {{ $this->isFavourite($product->id) ? '♥ Favourited' : '♡ Favourite' }}
                            </button>
                            @php
                                $isSubscribed = in_array((int) $product->id, array_map('intval', $subscribedProductIds ?? []), true);
                            @endphp
                            <button
                                type="button"
                                class="btn btn-subscription open-subscription-modal {{ $isSubscribed ? 'is-subscribed' : '' }}"
                                data-product-id="{{ $product->id }}"
                                data-product-name="{{ $product->name }}"
                                data-subscribed="{{ $isSubscribed ? '1' : '0' }}"
                                @if($isSubscribed) disabled @endif
                            >
                                {{ $isSubscribed ? 'Subscribed ✓' : 'Subscribe' }}
                            </button>
                        </div>
                    @endunless
                </div>
            </div>
        @empty
            <p class="empty-products">No products available for subscription yet.</p>
        @endforelse
    </div>

    <div id="subscription-modal" class="subscription-modal" aria-hidden="true">
        <div class="subscription-modal-backdrop"></div>
        <div class="subscription-modal-content" role="dialog" aria-modal="true" aria-labelledby="subscription-modal-title">
            <button type="button" class="subscription-modal-close" id="subscription-modal-close" aria-label="Close">×</button>
            <h3 id="subscription-modal-title">Choose a subscription plan</h3>
            <p class="subscription-modal-product" id="subscription-modal-product"></p>

            <div id="subscription-plan-list" class="subscription-plan-list" role="radiogroup" aria-labelledby="subscription-modal-title"></div>

            <div id="subscription-card-element" class="subscription-card-element"></div>
            <p id="subscription-errors" class="payment-errors"></p>

            <div class="subscription-modal-actions">
                <button type="button" class="btn btn-secondary" id="subscription-modal-cancel">Cancel</button>
                <button type="button" class="btn btn-primary" id="subscription-modal-submit">Continue</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
(function() {
    if (window.__subscriptionModalInitialized) return;
    window.__subscriptionModalInitialized = true;

    var stripeKey = @json(config('services.stripe.key'));
    if (!stripeKey) return;

    var plansByProduct = @json($plansByProductForJs ?? []);

    var stripe = Stripe(stripeKey);
    var elements = stripe.elements();
    var card = elements.create('card');
    var cardMounted = false;
    var selectedProductId = null;
    var selectedProductName = '';

    var modal = document.getElementById('subscription-modal');
    if (!modal) return;

    var closeBtn = document.getElementById('subscription-modal-close');
    var cancelBtn = document.getElementById('subscription-modal-cancel');
    var submitBtn = document.getElementById('subscription-modal-submit');
    var errorEl = document.getElementById('subscription-errors');
    var productNameEl = document.getElementById('subscription-modal-product');
    var planListEl = document.getElementById('subscription-plan-list');
    var cardElSelector = '#subscription-card-element';

    function setError(message) {
        errorEl.textContent = message || '';
    }

    function renderPlanRadios(productId) {
        if (!planListEl) return;
        var pid = parseInt(productId, 10);
        var plans = plansByProduct[pid] || [];
        planListEl.innerHTML = '';
        if (!plans.length) {
            planListEl.innerHTML = '<p class="payment-errors">No subscription plans for this product. Ask an admin to run the subscription plan seeder.</p>';
            return;
        }
        plans.forEach(function(p, idx) {
            var label = document.createElement('label');
            label.className = 'subscription-plan-option';
            var input = document.createElement('input');
            input.type = 'radio';
            input.name = 'subscription-plan';
            input.value = String(p.id);
            if (idx === 0) input.checked = true;
            var span = document.createElement('span');
            span.innerHTML = '<strong>' + escapeHtml(p.title) + '</strong>';
            label.appendChild(input);
            label.appendChild(span);
            planListEl.appendChild(label);
        });
    }

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }

    function openModal(productId, productName, triggerBtn) {
        if (triggerBtn && (triggerBtn.disabled || triggerBtn.getAttribute('data-subscribed') === '1')) {
            return;
        }
        selectedProductId = productId;
        selectedProductName = productName || '';
        productNameEl.textContent = selectedProductName;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        setError('');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Continue';
        renderPlanRadios(productId);

        if (!cardMounted) {
            card.mount(cardElSelector);
            cardMounted = true;
        }
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Continue';
        setError('');
    }

    function selectedSubscriptionPlanId() {
        var plan = document.querySelector('input[name="subscription-plan"]:checked');
        return plan ? parseInt(plan.value, 10) : null;
    }

    function postJson(url, payload) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload),
            credentials: 'same-origin'
        }).then(function(response) {
            return response.text().then(function(text) {
                var data = {};
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (parseErr) {
                    var err = new Error(
                        'Invalid server response (HTTP ' + response.status + '). Try refreshing the page or logging in again.'
                    );
                    err.httpStatus = response.status;
                    throw err;
                }
                if (!response.ok) {
                    var message = (data && (data.error || data.message))
                        ? (data.error || data.message)
                        : ('Request failed (HTTP ' + response.status + ').');
                    var httpErr = new Error(message);
                    httpErr.httpStatus = response.status;
                    throw httpErr;
                }
                return data;
            });
        });
    }

    function handleSubscription() {
        if (!selectedProductId) {
            setError('Please choose a product first.');
            return;
        }

        var subscriptionPlanId = selectedSubscriptionPlanId();
        if (!subscriptionPlanId) {
            setError('Please select a subscription plan.');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing...';
        setError('');

        postJson('{{ route('stripe.subscriptions.create-intent') }}', {
            subscription_plan_id: subscriptionPlanId
        })
            .then(function(intentData) {
                if (intentData.mode === 'trial_setup_intent') {
                    return stripe.confirmCardSetup(intentData.clientSecret, {
                        payment_method: {
                            card: card
                        }
                    }).then(function(result) {
                        if (result.error) {
                            throw new Error(result.error.message);
                        }

                        return postJson('{{ route('stripe.subscriptions.confirm-trial-monthly') }}', {
                            subscription_plan_id: subscriptionPlanId,
                            setup_intent_id: result.setupIntent.id
                        });
                    });
                }

                if (intentData.mode === 'plan_initial_payment') {
                    return stripe.confirmCardPayment(intentData.clientSecret, {
                        payment_method: {
                            card: card
                        }
                    }).then(function(result) {
                        if (result.error) {
                            throw new Error(result.error.message);
                        }

                        return postJson('{{ route('stripe.subscriptions.confirm-plan') }}', {
                            subscription_plan_id: subscriptionPlanId,
                            payment_intent_id: result.paymentIntent.id
                        });
                    });
                }

                throw new Error(intentData.error || 'Unsupported payment mode.');
            })
            .then(function(finalData) {
                closeModal();
                if (finalData && finalData.redirect) {
                    window.location.href = finalData.redirect;
                    return;
                }
                alert(finalData.message || 'Subscription created successfully.');
                window.location.reload();
            })
            .catch(function(err) {
                var msg = err.message || 'Subscription failed. Please try again.';
                setError(msg);
                if (err.httpStatus === 409 || (msg && msg.indexOf('already have an active subscription') !== -1)) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Already subscribed';
                    window.setTimeout(function() {
                        closeModal();
                        window.location.reload();
                    }, 900);
                    return;
                }
                submitBtn.disabled = false;
                submitBtn.textContent = 'Continue';
            });
    }

    document.addEventListener('click', function(e) {
        var trigger = e.target.closest('.open-subscription-modal');
        if (trigger) {
            if (trigger.disabled || trigger.getAttribute('data-subscribed') === '1') {
                return;
            }
            openModal(trigger.getAttribute('data-product-id'), trigger.getAttribute('data-product-name'), trigger);
            return;
        }

        if (e.target === closeBtn || e.target === cancelBtn || e.target.classList.contains('subscription-modal-backdrop')) {
            closeModal();
        }
    });

    submitBtn.addEventListener('click', handleSubscription);
    card.on('change', function(event) {
        setError(event.error ? event.error.message : '');
    });
})();
</script>
@endpush
