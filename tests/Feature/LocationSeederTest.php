<?php

use App\Models\Location;
use Database\Seeders\LocationSeeder;

test('the four confirmed facilities are seeded as active Texas facilities', function () {
    $this->seed(LocationSeeder::class);

    foreach (['College Station', 'Killeen', 'Preston', 'Frisco'] as $name) {
        $this->assertDatabaseHas('locations', [
            'name' => $name,
            'state' => 'TX',
            'is_active' => true,
            'name_to_be_confirmed' => false,
        ]);
    }
});

test('running the seeder twice does not create duplicate facilities or undo admin edits', function () {
    $this->seed(LocationSeeder::class);
    Location::where('name', 'Frisco')->update(['address' => '123 Main St']);

    $this->seed(LocationSeeder::class);

    expect(Location::count())->toBe(4);
    expect(Location::where('name', 'Frisco')->value('address'))->toBe('123 Main St');
});
