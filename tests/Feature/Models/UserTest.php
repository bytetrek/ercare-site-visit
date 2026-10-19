<?php

use App\Models\Location;
use App\Models\User;

test('admins and regional directors can view and inspect every facility without access rows', function (string $role) {
    $user = User::factory()->{$role}()->create();
    $activeLocation = Location::factory()->create(['name' => 'Killeen']);
    $inactiveLocation = Location::factory()->inactive()->create(['name' => 'Frisco']);

    expect($user->canView($activeLocation))->toBeTrue();
    expect($user->canInspect($activeLocation))->toBeTrue();
    expect($user->visibleFacilities()->pluck('name')->all())->toBe(['Frisco', 'Killeen']);
    expect($user->inspectableFacilities()->pluck('name')->all())->toBe(['Killeen']);
})->with([
    'admin' => 'admin',
    'regional director' => 'regionalDirector',
]);

test('a facility manager with no access rows has no facilities', function () {
    $manager = User::factory()->create();
    $location = Location::factory()->create();

    expect($manager->canView($location))->toBeFalse();
    expect($manager->canInspect($location))->toBeFalse();
    expect($manager->visibleFacilities())->toBeEmpty();
    expect($manager->inspectableFacilities())->toBeEmpty();
});

test('a facility manager with view access can view but not inspect', function () {
    $manager = User::factory()->create();
    $location = Location::factory()->create();
    $manager->locations()->attach($location, ['can_view' => true, 'can_inspect' => false]);

    expect($manager->canView($location))->toBeTrue();
    expect($manager->canInspect($location))->toBeFalse();
    expect($manager->visibleFacilities()->modelKeys())->toBe([$location->id]);
    expect($manager->inspectableFacilities())->toBeEmpty();
});

test('a facility manager only gets the facilities they were given, sorted by name', function () {
    $manager = User::factory()->create();
    $frisco = Location::factory()->create(['name' => 'Frisco']);
    $killeen = Location::factory()->create(['name' => 'Killeen']);
    Location::factory()->create(['name' => 'Preston']);
    $manager->locations()->attach($killeen, ['can_view' => true, 'can_inspect' => true]);
    $manager->locations()->attach($frisco, ['can_view' => true, 'can_inspect' => true]);

    expect($manager->visibleFacilities()->pluck('name')->all())->toBe(['Frisco', 'Killeen']);
    expect($manager->inspectableFacilities()->pluck('name')->all())->toBe(['Frisco', 'Killeen']);
});

test('inactive facilities stay visible but cannot be picked for a new inspection', function () {
    $manager = User::factory()->create();
    $location = Location::factory()->inactive()->create();
    $manager->locations()->attach($location, ['can_view' => true, 'can_inspect' => true]);

    expect($manager->visibleFacilities()->modelKeys())->toBe([$location->id]);
    expect($manager->inspectableFacilities())->toBeEmpty();
});

test('setting inspect access also turns on view access', function () {
    $manager = User::factory()->create();
    $location = Location::factory()->create();

    $manager->setFacilityAccess($location, false, true);

    expect($manager->canView($location))->toBeTrue();
    expect($manager->canInspect($location))->toBeTrue();
    $this->assertDatabaseHas('location_user', [
        'user_id' => $manager->id,
        'location_id' => $location->id,
        'can_view' => true,
        'can_inspect' => true,
    ]);
});

test('setting access updates an existing row instead of adding another', function () {
    $manager = User::factory()->create();
    $location = Location::factory()->create();
    $manager->locations()->attach($location, ['can_view' => true, 'can_inspect' => true]);

    $manager->setFacilityAccess($location, true, false);

    expect($manager->canView($location))->toBeTrue();
    expect($manager->canInspect($location))->toBeFalse();
    $this->assertDatabaseCount('location_user', 1);
});

test('removing all access deletes the access row', function () {
    $manager = User::factory()->create();
    $location = Location::factory()->create();
    $manager->locations()->attach($location, ['can_view' => true, 'can_inspect' => false]);

    $manager->setFacilityAccess($location, false, false);

    expect($manager->canView($location))->toBeFalse();
    $this->assertDatabaseCount('location_user', 0);
});
