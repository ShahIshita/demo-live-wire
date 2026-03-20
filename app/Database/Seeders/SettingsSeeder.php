<?php

namespace App\Database\Seeders;

use App\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run()
    {
        $defaults = [
            ['key' => 'site_name', 'value' => config('app.name'), 'group' => 'general'],
            ['key' => 'site_logo', 'value' => '', 'group' => 'general'],
            ['key' => 'site_email', 'value' => config('mail.from.address', 'admin@example.com'), 'group' => 'general'],
            ['key' => 'currency', 'value' => 'USD', 'group' => 'general'],
            ['key' => 'currency_symbol', 'value' => '$', 'group' => 'general'],
            ['key' => 'tax_rate', 'value' => '0', 'group' => 'general'],
            ['key' => 'tax_label', 'value' => 'Tax', 'group' => 'general'],
        ];

        foreach ($defaults as $s) {
            Setting::updateOrCreate(['key' => $s['key']], $s);
        }
    }
}
