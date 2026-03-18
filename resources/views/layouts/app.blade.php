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

        .user-info {
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            font-size: 14px;
        }

        .user-email {
            font-size: 12px;
            opacity: 0.9;
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
                <a href="{{ route('cart.index') }}" style="color: white; text-decoration: none; font-weight: 500;">Cart</a>
                <a href="{{ route('favourites.index') }}" style="color: white; text-decoration: none; font-weight: 500;">Favourites</a>
                @if (Auth::user()->is_admin ?? false)
                    <a href="{{ route('admin.products.index') }}" style="color: white; text-decoration: none; font-weight: 600;">Admin</a>
                @endif
                <div class="navbar-user">
                    <div class="user-info">
                        <div class="user-name">{{ Auth::user()->name }}</div>
                        <div class="user-email">{{ Auth::user()->email }}</div>
                    </div>
                    @livewire('auth.logout')
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

    @livewireScripts
</body>
</html>
