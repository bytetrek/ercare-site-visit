<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;

test('the two admins are seeded as active admins', function () {
    $this->seed(AdminUserSeeder::class);

    foreach (['bytetreklimited@gmail.com', 'kkhan@ercare24.com'] as $email) {
        $this->assertDatabaseHas('users', [
            'email' => $email,
            'role' => UserRole::Admin->value,
            'is_active' => true,
        ]);
    }
});

test('running the seeder twice does not create duplicate admins', function () {
    $this->seed(AdminUserSeeder::class);
    $this->seed(AdminUserSeeder::class);

    expect(User::count())->toBe(2);
});
