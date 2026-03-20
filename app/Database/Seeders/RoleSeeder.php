<?php

namespace App\Database\Seeders;

use App\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Full access to all admin features'],
            ['name' => 'Manager', 'slug' => 'manager', 'description' => 'Manage users, products, orders, reviews, shipping'],
            ['name' => 'Support', 'slug' => 'support', 'description' => 'View and manage orders, reviews'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
