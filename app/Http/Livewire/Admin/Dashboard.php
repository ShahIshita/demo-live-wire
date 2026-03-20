<?php

namespace App\Http\Livewire\Admin;

use App\Order;
use App\Product;
use App\User;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $totalUsers = User::count();
        $totalProducts = Product::count();
        $totalOrders = Order::count();
        $totalRevenue = Order::whereIn('status', ['delivered', 'shipped', 'processing'])
            ->where(function ($q) {
                $q->where('payment_status', 'paid')
                    ->orWhereNotNull('stripe_payment_status');
            })
            ->sum('total');

        $recentOrders = Order::with(['user', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('livewire.admin.dashboard', [
            'totalUsers' => $totalUsers,
            'totalProducts' => $totalProducts,
            'totalOrders' => $totalOrders,
            'totalRevenue' => $totalRevenue,
            'recentOrders' => $recentOrders,
        ]);
    }
}
