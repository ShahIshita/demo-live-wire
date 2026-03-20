<?php

namespace App\Http\Livewire\Admin;

use App\Order;
use Livewire\Component;
use Livewire\WithPagination;

class OrderList extends Component
{
    use WithPagination;

    public $editingOrderId = null;
    public $delivery_date = '';
    public $delivery_tracking_number = '';
    public $delivery_carrier = '';
    public $status = '';
    public $payment_status = '';
    public $search = '';
    public $statusFilter = '';

    protected $queryString = ['search' => ['except' => ''], 'statusFilter' => ['except' => '']];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }

    public function editOrder($orderId)
    {
        $order = Order::findOrFail($orderId);
        $this->editingOrderId = $orderId;
        $this->delivery_date = $order->delivery_date ? $order->delivery_date->format('Y-m-d') : '';
        $this->delivery_tracking_number = $order->delivery_tracking_number ?? '';
        $this->delivery_carrier = $order->delivery_carrier ?? '';
        $this->status = $order->status;
        $this->payment_status = $order->payment_status ?? 'pending';
    }

    public function cancelEdit()
    {
        $this->editingOrderId = null;
    }

    public function saveOrder()
    {
        $order = Order::findOrFail($this->editingOrderId);
        $order->update([
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'delivery_date' => $this->delivery_date ?: null,
            'delivery_tracking_number' => $this->delivery_tracking_number ?: null,
            'delivery_carrier' => $this->delivery_carrier ?: null,
        ]);
        session()->flash('message', 'Order updated.');
        $this->cancelEdit();
    }

    public function render()
    {
        $query = Order::with(['user', 'address', 'items.product']);
        if ($this->search) {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', '%' . $this->search . '%')->orWhere('email', 'like', '%' . $this->search . '%'))
                ->orWhere('id', $this->search);
        }
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }
        $orders = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('livewire.admin.order-list', ['orders' => $orders]);
    }
}
