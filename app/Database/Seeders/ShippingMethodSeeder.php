<?php

namespace App\Database\Seeders;

use App\ShippingMethod;
use Illuminate\Database\Seeder;

class ShippingMethodSeeder extends Seeder
{
    public function run()
    {
        $methods = [
            ['name' => 'Standard Shipping', 'description' => '5-7 business days', 'charge' => 5.99, 'min_order_amount' => 50, 'estimated_days' => 5, 'sort_order' => 1],
            ['name' => 'Express Shipping', 'description' => '2-3 business days', 'charge' => 12.99, 'min_order_amount' => 0, 'estimated_days' => 2, 'sort_order' => 2],
            ['name' => 'Free Shipping', 'description' => 'Orders over $50', 'charge' => 0, 'min_order_amount' => 50, 'estimated_days' => 7, 'sort_order' => 0],
        ];

        foreach ($methods as $m) {
            ShippingMethod::updateOrCreate(
                ['name' => $m['name']],
                array_merge($m, ['is_active' => true])
            );
        }
    }
}
