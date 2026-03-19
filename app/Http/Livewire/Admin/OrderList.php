<?php

namespace App\Http\Livewire\Admin;

use App\Order;
use Livewire\Component;

class OrderList extends Component
{
    public $editingOrderId = null;
    public $delivery_date = '';
    public $delivery_tracking_number = '';
    public $delivery_carrier = '';

    public function editDelivery($orderId)
    {
        $order = Order::findOrFail($orderId);
        $this->editingOrderId = $orderId;
        $this->delivery_date = $order->delivery_date ? $order->delivery_date->format('Y-m-d') : '';
        $this->delivery_tracking_number = $order->delivery_tracking_number ?? '';
        $this->delivery_carrier = $order->delivery_carrier ?? '';
    }

    public function cancelEdit()
    {
        $this->editingOrderId = null;
        $this->delivery_date = '';
        $this->delivery_tracking_number = '';
        $this->delivery_carrier = '';
    }

    public function saveDelivery()
    {
        $order = Order::findOrFail($this->editingOrderId);
        $order->update([
            'delivery_date' => $this->delivery_date ?: null,
            'delivery_tracking_number' => $this->delivery_tracking_number ?: null,
            'delivery_carrier' => $this->delivery_carrier ?: null,
        ]);
        session()->flash('message', 'Delivery info updated.');
        $this->cancelEdit();
    }

    public function render()
    {
        return view('livewire.admin.order-list', [
            'orders' => Order::with(['user', 'address', 'items.product'])->orderBy('created_at', 'desc')->get(),
        ]);
    }
}
