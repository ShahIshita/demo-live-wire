<?php

namespace App\Http\Livewire\Admin;

use App\Setting;
use Livewire\Component;

class SettingsForm extends Component
{
    public $site_name = '';
    public $site_logo = '';
    public $site_email = '';
    public $currency = 'USD';
    public $currency_symbol = '$';
    public $tax_rate = '0';
    public $tax_label = 'Tax';

    protected $rules = [
        'site_name' => 'required|min:2|max:100',
        'site_email' => 'required|email',
        'currency' => 'required|max:10',
        'currency_symbol' => 'required|max:5',
        'tax_rate' => 'required|numeric|min:0|max:100',
        'tax_label' => 'nullable|max:50',
    ];

    public function mount()
    {
        $this->site_name = Setting::get('site_name', config('app.name'));
        $this->site_logo = Setting::get('site_logo', '');
        $this->site_email = Setting::get('site_email', config('mail.from.address', ''));
        $this->currency = Setting::get('currency', 'USD');
        $this->currency_symbol = Setting::get('currency_symbol', '$');
        $this->tax_rate = Setting::get('tax_rate', '0');
        $this->tax_label = Setting::get('tax_label', 'Tax');
    }

    public function save()
    {
        $this->validate();
        Setting::set('site_name', $this->site_name);
        Setting::set('site_logo', $this->site_logo);
        Setting::set('site_email', $this->site_email);
        Setting::set('currency', $this->currency);
        Setting::set('currency_symbol', $this->currency_symbol);
        Setting::set('tax_rate', $this->tax_rate);
        Setting::set('tax_label', $this->tax_label);
        session()->flash('message', 'Settings saved.');
    }

    public function render()
    {
        return view('livewire.admin.settings-form');
    }
}
