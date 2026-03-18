<?php

use App\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Promote a user to admin. Run: php artisan db:seed --class=AdminUserSeeder
     * Set ADMIN_EMAIL in .env to promote specific user, or first user will be promoted.
     *
     * @return void
     */
    public function run()
    {
        $email = env('ADMIN_EMAIL');

        $user = $email
            ? User::where('email', $email)->first()
            : User::first();

        if (!$user) {
            $this->command->error('No user found. Register first, then run this seeder.');
            return;
        }

        $user->is_admin = true;
        $user->save();

        $this->command->info("User {$user->email} is now an admin.");
    }
}
