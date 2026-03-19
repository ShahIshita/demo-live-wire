<?php

namespace App\Http\Livewire\User;

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
        $order = Order::with(['address', 'items.product'])
            ->where('user_id', auth()->id())
            ->findOrFail($this->orderId);

        return view('livewire.user.order-detail', [
            'order' => $order,
        ]);
    }
}
