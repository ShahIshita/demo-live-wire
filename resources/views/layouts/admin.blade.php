<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Admin @yield('title', '')</title>

    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Nunito', sans-serif; background-color: #f7fafc; color: #2d3748; }

        .navbar {
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
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
        .navbar-brand { font-size: 24px; font-weight: 700; color: white; text-decoration: none; }
        .navbar-links { display: flex; align-items: center; gap: 24px; }
        .navbar-links a { color: white; text-decoration: none; font-weight: 500; }
        .navbar-links a:hover { opacity: 0.9; }
        .navbar-links .btn { background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); }
        .navbar-links .btn:hover { background: rgba(255,255,255,0.3); }

        .container { max-width: 1200px; margin: 0 auto; padding: 40px 24px; }

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
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
        .btn-secondary { background: #e2e8f0; color: #2d3748; }
        .btn-secondary:hover { background: #cbd5e0; }
        .btn-sm { padding: 6px 12px; font-size: 13px; }
        .btn-edit { background: #4299e1; color: white; }
        .btn-edit:hover { background: #3182ce; }
        .btn-delete { background: #fc8181; color: white; }
        .btn-delete:hover { background: #f56565; }

        .alert { padding: 16px 20px; border-radius: 8px; margin-bottom: 24px; font-size: 14px; }
        .alert-success { background-color: #c6f6d5; color: #22543d; border: 1px solid #9ae6b4; }

        .product-header, .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .product-header h2, .form-header h2 { font-size: 24px; font-weight: 700; color: #2d3748; }

        .product-table-container { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
        .product-table { width: 100%; border-collapse: collapse; }
        .product-table th, .product-table td { padding: 16px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .product-table th { background: #f7fafc; font-weight: 600; color: #4a5568; }
        .product-table tbody tr:hover { background: #f7fafc; }
        .product-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        .no-image { color: #a0aec0; font-size: 13px; }
        .desc-cell { max-width: 200px; }
        .empty-state { padding: 40px; text-align: center; color: #718096; background: white; border-radius: 12px; }
        .empty-state a { color: #667eea; }

        .product-form { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 32px; max-width: 600px; }
        .form-group { margin-bottom: 20px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #4a5568; }
        .form-control { width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; font-family: 'Nunito', sans-serif; }
        .form-control:focus { outline: none; border-color: #667eea; }
        .form-control.is-invalid { border-color: #f56565; }
        .error-message { display: block; color: #f56565; font-size: 13px; margin-top: 6px; }
        .required { color: #f56565; }
        .form-hint { font-size: 12px; color: #718096; margin-top: 4px; display: block; }
        .form-actions { display: flex; gap: 12px; margin-top: 24px; }
        .current-image { margin-bottom: 12px; }
        .current-image p { font-size: 13px; color: #718096; margin-bottom: 8px; }
    </style>

    @livewireStyles
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="{{ route('admin.products.index') }}" class="navbar-brand">{{ config('app.name', 'Laravel') }} Admin</a>
            <div class="navbar-links">
                <a href="{{ route('admin.products.index') }}">Products</a>
                <a href="{{ route('dashboard') }}">Dashboard</a>
                @livewire('auth.logout')
            </div>
        </div>
    </nav>

    <div class="container">
        @yield('content')
    </div>

    @livewireScripts
</body>
</html>
