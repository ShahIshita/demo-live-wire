<?php

namespace App\Http\Livewire\Admin;

use App\ShippingMethod;
use Livewire\Component;

class ShippingList extends Component
{
    public $editingId = null;
    public $name = '';
    public $description = '';
    public $charge = 0;
    public $min_order_amount = 0;
    public $estimated_days = null;
    public $is_active = true;
    public $sort_order = 0;

    protected $rules = [
        'name' => 'required|min:2|max:100',
        'charge' => 'required|numeric|min:0',
        'min_order_amount' => 'integer|min:0',
        'estimated_days' => 'nullable|integer|min:1',
        'sort_order' => 'integer|min:0',
    ];

    public function edit($id)
    {
        $m = ShippingMethod::findOrFail($id);
        $this->editingId = $id;
        $this->name = $m->name;
        $this->description = $m->description ?? '';
        $this->charge = $m->charge;
        $this->min_order_amount = $m->min_order_amount ?? 0;
        $this->estimated_days = $m->estimated_days ? (string) $m->estimated_days : '';
        $this->is_active = $m->is_active;
        $this->sort_order = $m->sort_order;
    }

    public function cancelEdit()
    {
        $this->editingId = null;
    }

    public function save()
    {
        $this->validate();
        $m = ShippingMethod::findOrFail($this->editingId);
        $m->update([
            'name' => $this->name,
            'description' => $this->description ?: null,
            'charge' => $this->charge,
            'min_order_amount' => (int) $this->min_order_amount,
            'estimated_days' => $this->estimated_days ? (int) $this->estimated_days : null,
            'is_active' => $this->is_active,
            'sort_order' => (int) $this->sort_order,
        ]);
        session()->flash('message', 'Shipping method updated.');
        $this->cancelEdit();
    }

    public function toggleActive($id)
    {
        $m = ShippingMethod::findOrFail($id);
        $m->is_active = !$m->is_active;
        $m->save();
        session()->flash('message', 'Shipping method ' . ($m->is_active ? 'enabled' : 'disabled') . '.');
    }

    public function render()
    {
        $methods = ShippingMethod::orderBy('sort_order')->orderBy('name')->get();
        return view('livewire.admin.shipping-list', ['methods' => $methods]);
    }
}
