<?php

use App\Livewire\Admin\LocationPermissions;
use App\Models\Location;
use App\Models\User;
use Livewire\Livewire;

test('admins can open the facility access page', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->regionalDirector()->create(['name' => 'Rita Director']);
    User::factory()->create(['name' => 'Maria Gonzalez']);
    Location::factory()->create(['name' => 'Frisco']);
    Location::factory()->inactive()->create(['name' => 'Old Clinic']);

    $this->actingAs($admin)
        ->get(route('admin.access'))
        ->assertOk()
        ->assertSee('Rita Director')
        ->assertSee('All facilities, automatically')
        ->assertSee('Maria Gonzalez')
        ->assertSee('Frisco')
        ->assertDontSee('Old Clinic');
});

test('access changes are forbidden for non-admins', function () {
    $manager = User::factory()->create();
    $location = Location::factory()->create();

    Livewire::actingAs($manager)
        ->test(LocationPermissions::class)
        ->call('toggleInspect', $manager->id, $location->id)
        ->assertForbidden();

    $this->assertDatabaseCount('location_user', 0);
});

test('ticking view gives view-only access', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->create();
    $location = Location::factory()->create();

    Livewire::actingAs($admin)
        ->test(LocationPermissions::class)
        ->call('toggleView', $manager->id, $location->id);

    expect($manager->canView($location))->toBeTrue();
    expect($manager->canInspect($location))->toBeFalse();
});

test('ticking inspect also ticks view', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->create();
    $location = Location::factory()->create();

    Livewire::actingAs($admin)
        ->test(LocationPermissions::class)
        ->call('toggleInspect', $manager->id, $location->id);

    expect($manager->canView($location))->toBeTrue();
    expect($manager->canInspect($location))->toBeTrue();
});

test('unticking view also unticks inspect', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->create();
    $location = Location::factory()->create();
    $manager->locations()->attach($location, ['can_view' => true, 'can_inspect' => true]);

    Livewire::actingAs($admin)
        ->test(LocationPermissions::class)
        ->call('toggleView', $manager->id, $location->id);

    expect($manager->canView($location))->toBeFalse();
    expect($manager->canInspect($location))->toBeFalse();
});

test('unticking inspect keeps view', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->create();
    $location = Location::factory()->create();
    $manager->locations()->attach($location, ['can_view' => true, 'can_inspect' => true]);

    Livewire::actingAs($admin)
        ->test(LocationPermissions::class)
        ->call('toggleInspect', $manager->id, $location->id);

    expect($manager->canView($location))->toBeTrue();
    expect($manager->canInspect($location))->toBeFalse();
});

test('access rows cannot be set for admins or regional directors', function (string $factoryState) {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->{$factoryState}()->create();
    $location = Location::factory()->create();

    Livewire::actingAs($admin)
        ->test(LocationPermissions::class)
        ->call('toggleInspect', $user->id, $location->id)
        ->assertNotFound();

    $this->assertDatabaseCount('location_user', 0);
})->with([
    'admin' => 'admin',
    'regional director' => 'regionalDirector',
]);
