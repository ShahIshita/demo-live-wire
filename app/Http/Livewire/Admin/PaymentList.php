<?php

namespace App\Http\Livewire\Admin;

use App\Order;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentList extends Component
{
    use WithPagination;

    public $statusFilter = '';

    protected $queryString = ['statusFilter' => ['except' => '']];

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Order::with('user');
        if ($this->statusFilter) {
            $query->where('payment_status', $this->statusFilter);
        }
        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('livewire.admin.payment-list', ['orders' => $orders]);
    }
}
