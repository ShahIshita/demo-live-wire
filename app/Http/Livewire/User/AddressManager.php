<?php

namespace App\Http\Livewire\User;

use App\Address;
use Livewire\Component;

class AddressManager extends Component
{
    public $addresses = [];
    public $showForm = false;
    public $editingId = null;
    public $label = '';
    public $address_line1 = '';
    public $address_line2 = '';
    public $city = '';
    public $state = '';
    public $postal_code = '';
    public $country = 'US';
    public $latitude = '';
    public $longitude = '';
    public $is_default = false;

    protected $rules = [
        'address_line1' => 'required|string|max:255',
        'city' => 'required|string|max:100',
        'country' => 'required|string|max:100',
    ];

    public function mount()
    {
        $this->loadAddresses();
    }

    public function loadAddresses()
    {
        $this->addresses = auth()->user()->addresses()->orderBy('is_default', 'desc')->get()->toArray();
    }

    public function openForm($addressId = null)
    {
        $this->resetForm();
        if ($addressId) {
            $addr = Address::where('user_id', auth()->id())->findOrFail($addressId);
            $this->editingId = $addr->id;
            $this->label = $addr->label ?? '';
            $this->address_line1 = $addr->address_line1;
            $this->address_line2 = $addr->address_line2 ?? '';
            $this->city = $addr->city;
            $this->state = $addr->state ?? '';
            $this->postal_code = $addr->postal_code ?? '';
            $this->country = $addr->country ?? 'US';
            $this->latitude = $addr->latitude ?? '';
            $this->longitude = $addr->longitude ?? '';
            $this->is_default = (bool) $addr->is_default;
        }
        $this->showForm = true;
    }

    public function closeForm()
    {
        $this->showForm = false;
        $this->resetForm();
        $this->loadAddresses();
    }

    public function resetForm()
    {
        $this->editingId = null;
        $this->label = '';
        $this->address_line1 = '';
        $this->address_line2 = '';
        $this->city = '';
        $this->state = '';
        $this->postal_code = '';
        $this->country = 'US';
        $this->latitude = '';
        $this->longitude = '';
        $this->is_default = false;
        $this->resetValidation();
    }

    public function setAddressFromPlace($addressLine1, $city, $state, $postalCode, $country)
    {
        $this->address_line1 = $addressLine1;
        $this->city = $city;
        $this->state = $state;
        $this->postal_code = $postalCode;
        $this->country = $country;
    }

    public function save()
    {
        $this->validate($this->rules);

        if ($this->is_default) {
            Address::where('user_id', auth()->id())->update(['is_default' => false]);
        }

        $data = [
            'user_id' => auth()->id(),
            'label' => $this->label ?: null,
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2 ?: null,
            'city' => $this->city,
            'state' => $this->state ?: null,
            'postal_code' => $this->postal_code ?: null,
            'country' => $this->country,
            'latitude' => $this->latitude ?: null,
            'longitude' => $this->longitude ?: null,
            'is_default' => $this->is_default,
        ];

        if ($this->editingId) {
            Address::where('user_id', auth()->id())->findOrFail($this->editingId)->update($data);
            session()->flash('message', 'Address updated.');
        } else {
            Address::create($data);
            session()->flash('message', 'Address added.');
        }

        $this->closeForm();
    }

    public function setDefault($addressId)
    {
        Address::where('user_id', auth()->id())->update(['is_default' => false]);
        Address::where('user_id', auth()->id())->findOrFail($addressId)->update(['is_default' => true]);
        session()->flash('message', 'Default address updated.');
        $this->loadAddresses();
    }

    public function deleteAddress($addressId)
    {
        Address::where('user_id', auth()->id())->findOrFail($addressId)->delete();
        session()->flash('message', 'Address removed.');
        $this->loadAddresses();
    }

    public function render()
    {
        return view('livewire.user.address-manager');
    }
}
