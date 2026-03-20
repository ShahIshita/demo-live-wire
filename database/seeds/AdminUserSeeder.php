<?php

use App\Database\Seeders\RoleSeeder;
use App\Role;
use App\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Promote a user to admin. Run: php artisan db:seed --class=AdminUserSeeder
     * Set ADMIN_EMAIL in .env to promote specific user, or first user will be promoted.
     */
    public function run()
    {
        $this->call(RoleSeeder::class);

        $email = env('ADMIN_EMAIL');
        $user = $email ? User::where('email', $email)->first() : User::first();

        if (!$user) {
            $this->command->error('No user found. Register first, then run this seeder.');
            return;
        }

        $user->is_admin = true;
        $user->is_active = true;
        $user->save();

        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole && !$user->roles()->where('role_id', $adminRole->id)->exists()) {
            $user->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        $this->command->info("User {$user->email} is now an admin.");
    }
}
