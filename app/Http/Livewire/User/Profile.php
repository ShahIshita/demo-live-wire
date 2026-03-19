<?php

namespace App\Http\Livewire\User;

use Livewire\Component;

class Profile extends Component
{
    public $activeTab = 'orders';

    public function mount()
    {
        if (request()->has('tab') && in_array(request('tab'), ['orders', 'address'])) {
            $this->activeTab = request('tab');
        }
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        $orders = auth()->user()->orders()->with(['address', 'items.product'])->orderBy('created_at', 'desc')->get();

        return view('livewire.user.profile', [
            'orders' => $orders,
        ]);
    }
}
