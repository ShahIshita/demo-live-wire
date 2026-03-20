<?php

namespace App\Http\Livewire\Admin;

use App\Order;
use Livewire\Component;

class OrderDetail extends Component
{
    public $orderId;

    public function mount($orderId)
    {
        $this->orderId = $orderId;
    }

    public function render()
    {
        $order = Order::with(['user', 'address', 'items.product', 'shippingMethod'])
            ->findOrFail($this->orderId);

        return view('livewire.admin.order-detail', ['order' => $order]);
    }
}
