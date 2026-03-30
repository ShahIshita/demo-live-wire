<?php

use App\Http\Controllers\StripeController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::post('/stripe/webhook', [StripeController::class, 'handleWebhook'])->name('stripe.webhook');

// Guest routes (accessible only when not authenticated)
Route::middleware('guest')->group(function () {
    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');

    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');
});

// Authenticated routes (accessible only when authenticated)
Route::middleware('auth')->group(function () {
    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login')->with('message', 'You have been logged out successfully.');
    })->name('logout');

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    /** Featured products + subscription module (4 items, 3 plan types). */
    Route::get('/products', function () {
        return view('products-tab');
    })->name('products.tab');

    // User cart and favourites
    Route::get('/cart', function () {
        return view('cart');
    })->name('cart.index');

    Route::get('/favourites', function () {
        return view('favourites');
    })->name('favourites.index');

    Route::get('/profile', function () {
        return view('profile');
    })->name('profile.index');

    Route::get('/addresses', function () {
        return view('addresses');
    })->name('addresses.index');

    Route::get('/checkout', function () {
        return view('checkout');
    })->name('checkout.index');

    Route::get('/orders/{id}', function ($id) {
        return view('order-detail', ['orderId' => $id]);
    })->name('orders.show');

    Route::post('/stripe/create-payment-intent', [StripeController::class, 'createPaymentIntent'])->name('stripe.create-intent');
    Route::post('/stripe/confirm-order', [StripeController::class, 'confirmOrder'])->name('stripe.confirm-order');
    Route::post('/stripe/subscriptions/create-intent', [SubscriptionController::class, 'createIntent'])->name('stripe.subscriptions.create-intent');
    Route::post('/stripe/subscriptions/confirm-plan', [SubscriptionController::class, 'confirmPlanSubscription'])->name('stripe.subscriptions.confirm-plan');
    Route::post('/stripe/subscriptions/confirm-trial-monthly', [SubscriptionController::class, 'confirmTrialMonthly'])->name('stripe.subscriptions.confirm-trial-monthly');
    Route::get('/subscriptions/{subscriptionId}', [SubscriptionController::class, 'show'])->name('subscriptions.show');

    // Admin routes (admin-only access)
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'))->name('index');
        Route::get('/dashboard', fn () => view('admin.dashboard'))->name('dashboard');

        Route::get('/products', fn () => view('admin.products.index'))->name('products.index');
        Route::get('/products/create', fn () => view('admin.products.create'))->name('products.create');
        Route::get('/products/{id}/edit', fn ($id) => view('admin.products.edit', ['productId' => $id]))->name('products.edit');

        Route::get('/categories', fn () => view('admin.categories.index'))->name('categories.index');
        Route::get('/categories/create', fn () => view('admin.categories.create'))->name('categories.create');
        Route::get('/categories/{id}/edit', fn ($id) => view('admin.categories.edit', ['categoryId' => $id]))->name('categories.edit');

        Route::get('/orders', fn () => view('admin.orders.index'))->name('orders.index');
        Route::get('/orders/{id}', fn ($id) => view('admin.orders.show', ['orderId' => $id]))->name('orders.show');

        Route::get('/payments', fn () => view('admin.payments.index'))->name('payments.index');

        Route::get('/users', fn () => view('admin.users.index'))->name('users.index');

        Route::get('/reviews', fn () => view('admin.reviews.index'))->name('reviews.index');

        Route::get('/shipping', fn () => view('admin.shipping.index'))->name('shipping.index');

        Route::get('/settings', fn () => view('admin.settings.index'))->name('settings.index');
    });
});
