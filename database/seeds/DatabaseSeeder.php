<?php

use App\Database\Seeders\RoleSeeder;
use App\Database\Seeders\SettingsSeeder;
use App\Database\Seeders\ShippingMethodSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(RoleSeeder::class);
        $this->call(SettingsSeeder::class);
        $this->call(ShippingMethodSeeder::class);
    }
}
