<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Create (or update) the admin users.
     *
     * Admins sign in with Google, so they do not need a password.
     * Their real name is copied from Google the first time they sign in.
     */
    public function run(): void
    {
        $adminEmails = [
            'bytetreklimited@gmail.com',
            'kkhan@ercare24.com',
        ];

        foreach ($adminEmails as $email) {
            // updateOrCreate keeps the seeder safe to run more than once.
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $email,
                    'role' => UserRole::Admin,
                    'is_active' => true,
                ]
            );
        }
    }
}
