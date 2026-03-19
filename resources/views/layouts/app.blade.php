<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - @yield('title', 'Dashboard')</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f7fafc;
            color: #2d3748;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
        }

        .navbar-brand {
            font-size: 24px;
            font-weight: 700;
            color: white;
            text-decoration: none;
        }

        .navbar-menu {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .navbar-user {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .account-dropdown {
            position: relative;
        }

        .account-trigger {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: rgba(255,255,255,0.15);
            border-radius: 8px;
            cursor: pointer;
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            border: none;
        }

        .account-trigger:hover {
            background: rgba(255,255,255,0.25);
        }

        .account-trigger .chevron {
            font-size: 10px;
            transition: transform 0.2s;
        }

        .account-dropdown.open .account-trigger .chevron {
            transform: rotate(180deg);
        }

        .account-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 8px;
            min-width: 220px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            padding: 12px 0;
            z-index: 1000;
        }

        .account-dropdown.open .account-menu {
            display: block;
        }

        .account-menu-header {
            padding: 8px 20px 12px;
            font-weight: 700;
            font-size: 14px;
            color: #2d3748;
            border-bottom: 1px solid #e2e8f0;
        }

        .account-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #4a5568;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s;
        }

        .account-menu-item:hover {
            background: #f7fafc;
        }

        .account-menu-item .icon {
            width: 20px;
            text-align: center;
            font-size: 16px;
            color: #718096;
        }

        .account-menu-item.logout {
            border-top: 1px solid #e2e8f0;
            margin-top: 4px;
            padding-top: 12px;
            color: #e53e3e;
        }

        .account-menu-item.logout:hover {
            background: #fff5f5;
        }

        .cart-badge {
            background: #e53e3e;
            color: white;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 4px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Nunito', sans-serif;
            text-decoration: none;
            display: inline-block;
        }

        .btn-secondary {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 24px;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 32px;
            margin-bottom: 24px;
        }

        .card-header {
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #e2e8f0;
        }

        .card-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: #2d3748;
        }

        .card-body {
            color: #4a5568;
            line-height: 1.6;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .alert-success {
            background-color: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #667eea;
        }

        .stat-label {
            color: #718096;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .stat-value {
            color: #2d3748;
            font-size: 32px;
            font-weight: 700;
        }

        /* Product Grid & Cards */
        .welcome-banner { margin-bottom: 24px; }
        .welcome-banner h2 { font-size: 24px; color: #2d3748; margin-bottom: 4px; }
        .welcome-banner p { color: #718096; }
        .products-header, .cart-header, .fav-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .products-header h2, .cart-header h2, .fav-header h2 { font-size: 24px; font-weight: 700; color: #2d3748; }
        .header-links { display: flex; gap: 12px; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-primary:hover { transform: translateY(-1px); }
        .btn-cart, .btn-favourite { background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 24px;
        }
        .product-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .product-card-image {
            height: 180px;
            background: #f7fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .product-card-image img { width: 100%; height: 100%; object-fit: cover; }
        .no-image-placeholder { color: #a0aec0; font-size: 14px; }
        .product-card-body { padding: 20px; }
        .product-name { font-size: 18px; font-weight: 700; color: #2d3748; margin-bottom: 8px; }
        .product-desc { font-size: 14px; color: #718096; margin-bottom: 8px; line-height: 1.4; }
        .product-price { font-size: 20px; font-weight: 700; color: #667eea; margin-bottom: 4px; }
        .product-stock { font-size: 12px; color: #718096; margin-bottom: 16px; }
        .product-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-add-cart { background: #667eea; color: white; padding: 8px 16px; }
        .btn-remove-fav { background: #e2e8f0; color: #4a5568; padding: 8px 16px; }
        .btn-fav { background: #e2e8f0; color: #4a5568; padding: 8px 16px; }
        .btn-fav.is-favourite { background: #fc8181; color: white; }
        .empty-products, .empty-cart, .empty-fav { text-align: center; padding: 60px 20px; }

        /* Cart */
        .cart-table table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .cart-table th, .cart-table td { padding: 16px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .cart-table th { background: #f7fafc; font-weight: 600; }
        .cart-product { display: flex; align-items: center; gap: 16px; }
        .cart-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        .cart-no-img { width: 60px; height: 60px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; border-radius: 8px; color: #a0aec0; }
        .qty-controls { display: flex; align-items: center; gap: 8px; }
        .qty-btn { width: 32px; height: 32px; border: 1px solid #e2e8f0; background: white; border-radius: 6px; cursor: pointer; font-size: 18px; }
        .qty-value { min-width: 24px; text-align: center; }
        .btn-remove { background: #fc8181; color: white; padding: 6px 12px; font-size: 13px; }
        .cart-total { margin-top: 24px; font-size: 20px; }

        /* Checkout */
        .checkout-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .checkout-header h2 { font-size: 24px; font-weight: 700; color: #2d3748; }
        .checkout-summary { margin-top: 16px; }
        .checkout-title { font-size: 18px; font-weight: 600; margin-bottom: 16px; color: #2d3748; }
        .checkout-table table { width: 100%; border-collapse: collapse; }
        .checkout-table th, .checkout-table td { padding: 16px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .checkout-table th { background: #f7fafc; font-weight: 600; }
        .checkout-product { display: flex; align-items: flex-start; gap: 16px; }
        .checkout-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        .checkout-no-img { width: 60px; height: 60px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; border-radius: 8px; color: #a0aec0; }
        .checkout-desc { font-size: 12px; color: #718096; margin-top: 4px; }
        .checkout-total { margin-top: 24px; font-size: 20px; padding-top: 16px; border-top: 2px solid #e2e8f0; }
        .checkout-actions { margin-top: 24px; display: flex; flex-direction: column; gap: 12px; }
        .checkout-note { font-size: 13px; color: #718096; }
        .empty-checkout { text-align: center; padding: 60px 20px; }
        .alert-danger { background: #fed7d7; color: #c53030; border: 1px solid #fc8181; }
        .payment-errors { color: #c53030; font-size: 14px; margin-top: 8px; }
        #card-element { padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; background: white; min-height: 40px; }
        .step-indicator { display: flex; gap: 16px; margin-bottom: 24px; }
        .step-indicator .step { padding: 8px 16px; background: #e2e8f0; border-radius: 8px; }
        .step-indicator .step.active { background: #667eea; color: white; }
        .address-select-list { display: grid; gap: 12px; }
        .address-option { padding: 16px; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; }
        .address-option:hover, .address-option.selected { border-color: #667eea; background: #f7fafc; }
        .selected-address-box { background: #f7fafc; padding: 16px; border-radius: 8px; margin-bottom: 20px; }
        .step-actions { display: flex; gap: 12px; margin-top: 20px; }

        /* Addresses */
        .addresses-header, .address-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .addresses-header h2, .address-header h3 { font-size: 24px; font-weight: 700; color: #2d3748; }
        .address-form { margin-bottom: 24px; }
        .address-form h4 { margin-bottom: 16px; font-size: 18px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 6px; color: #4a5568; }
        .form-control { width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; font-family: inherit; }
        .form-control:focus { outline: none; border-color: #667eea; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
        .form-actions { display: flex; gap: 12px; margin-top: 20px; }
        .error-message { color: #f56565; font-size: 13px; display: block; margin-top: 4px; }
        .required { color: #f56565; }
        .checkbox-label { font-weight: normal; display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .address-list { display: grid; gap: 16px; }
        .address-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); position: relative; }
        .address-card.is-default { border-left: 4px solid #667eea; }
        .default-badge { background: #667eea; color: white; font-size: 11px; padding: 2px 8px; border-radius: 4px; position: absolute; top: 12px; right: 12px; }
        .address-actions { margin-top: 12px; display: flex; gap: 8px; }
        .btn-sm { padding: 6px 12px; font-size: 13px; }
        .btn-edit { background: #4299e1; color: white; }
        .btn-delete { background: #fc8181; color: white; }
        .empty-addresses { padding: 40px; text-align: center; color: #718096; }

        /* Order confirmation */
        .order-success-banner { background: #c6f6d5; border: 1px solid #9ae6b4; border-radius: 8px; padding: 24px; margin-bottom: 24px; }
        .order-success-banner h2 { color: #22543d; margin-bottom: 8px; }
        .order-details h3, .order-details h4 { margin: 16px 0 8px; color: #2d3748; }
        .order-status { color: #4a5568; }
        .order-address { background: #f7fafc; padding: 16px; border-radius: 8px; margin: 16px 0; }
        .order-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .order-table th, .order-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .order-table th { background: #f7fafc; }
        .order-product { display: flex; align-items: center; gap: 12px; }
        .order-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; }
        .order-no-img { width: 50px; height: 50px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; border-radius: 8px; color: #a0aec0; }
        .order-total { font-size: 20px; margin-top: 16px; padding-top: 16px; border-top: 2px solid #e2e8f0; }
        .order-actions { margin-top: 24px; }
        .order-delivery-info { background: #ebf8ff; border: 1px solid #90cdf4; border-radius: 8px; padding: 16px; margin: 16px 0; }

        /* Profile */
        .profile-header { margin-bottom: 24px; }
        .profile-header h2 { font-size: 24px; font-weight: 700; color: #2d3748; }
        .profile-subtitle { color: #718096; margin-top: 4px; }
        .profile-tabs { display: flex; gap: 8px; margin-bottom: 24px; }
        .profile-tab { padding: 10px 20px; border: 2px solid #e2e8f0; background: white; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; }
        .profile-tab:hover { border-color: #667eea; }
        .profile-tab.active { background: #667eea; color: white; border-color: #667eea; }
        .profile-content h3 { font-size: 18px; margin-bottom: 16px; color: #2d3748; }
        .orders-list { display: grid; gap: 16px; }
        .order-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .order-card-header { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 12px; }
        .order-status-badge { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
        .order-status-badge.placed { background: #c6f6d5; color: #22543d; }
        .order-status-badge.shipped { background: #bee3f8; color: #2c5282; }
        .order-status-badge.delivered { background: #c6f6d5; color: #22543d; }
        .order-status-badge.pending { background: #feebc8; color: #c05621; }
        .order-date { color: #718096; font-size: 13px; margin-left: auto; }
        .order-card-body p { margin-bottom: 6px; font-size: 14px; }
        .delivery-tracking { color: #667eea; }
        .order-card-actions { margin-top: 12px; }
    </style>

    @livewireStyles
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="{{ route('dashboard') }}" class="navbar-brand">
                {{ config('app.name', 'Laravel') }}
            </a>
            <div class="navbar-menu">
                <a href="{{ route('dashboard') }}" style="color: white; text-decoration: none; font-weight: 500;">Products</a>
                @unless (Auth::user()->is_admin ?? false)
                    <a href="{{ route('cart.index') }}" style="color: white; text-decoration: none; font-weight: 500; display: flex; align-items: center;">
                        Cart
                        @php $cartCount = Auth::user()->cart ? Auth::user()->cart->items()->sum('quantity') : 0; @endphp
                        @if ($cartCount > 0)
                            <span class="cart-badge">{{ $cartCount }}</span>
                        @endif
                    </a>
                @endunless
                <div class="navbar-user">
                    <div class="account-dropdown" id="accountDropdown">
                        <button type="button" class="account-trigger" onclick="document.getElementById('accountDropdown').classList.toggle('open')">
                            {{ Auth::user()->name }}
                            <span class="chevron">▼</span>
                        </button>
                        <div class="account-menu">
                            <div class="account-menu-header">Your Account</div>
                            @if (Auth::user()->is_admin ?? false)
                                <a href="{{ route('admin.products.index') }}" class="account-menu-item">
                                    <span class="icon">📦</span> Products
                                </a>
                                <a href="{{ route('admin.orders.index') }}" class="account-menu-item">
                                    <span class="icon">📋</span> Orders
                                </a>
                                <a href="{{ route('dashboard') }}" class="account-menu-item">
                                    <span class="icon">🏠</span> Dashboard
                                </a>
                            @else
                                <a href="{{ route('profile.index') }}" class="account-menu-item">
                                    <span class="icon">👤</span> My Profile
                                </a>
                                <a href="{{ route('profile.index') }}?tab=orders" class="account-menu-item">
                                    <span class="icon">📦</span> Orders
                                </a>
                                <a href="{{ route('profile.index') }}?tab=address" class="account-menu-item">
                                    <span class="icon">📍</span> Saved Addresses
                                </a>
                                <a href="{{ route('favourites.index') }}" class="account-menu-item">
                                    <span class="icon">♥</span> Wishlist
                                </a>
                            @endif
                            <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                                @csrf
                                <button type="submit" class="account-menu-item logout" style="width: 100%; border: none; background: none; cursor: pointer; text-align: left; font-family: inherit;">
                                    <span class="icon">⎋</span> Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        @if (session()->has('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif

        @yield('content')
    </div>

    @stack('scripts')
    <script>
        document.addEventListener('click', function(e) {
            var dd = document.getElementById('accountDropdown');
            if (dd && !dd.contains(e.target)) dd.classList.remove('open');
        });
    </script>
    @livewireScripts
</body>
</html>
