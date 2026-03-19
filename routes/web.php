<?php

use App\Http\Controllers\StripeController;
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

    // Admin routes (admin-only access)
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/products', function () {
            return view('admin.products.index');
        })->name('products.index');

        Route::get('/products/create', function () {
            return view('admin.products.create');
        })->name('products.create');

        Route::get('/products/{id}/edit', function ($id) {
            return view('admin.products.edit', ['productId' => $id]);
        })->name('products.edit');

        Route::get('/orders', function () {
            return view('admin.orders.index');
        })->name('orders.index');
    });
});
