<?php

use App\Livewire\Admin\LocationManagement;
use App\Models\Location;
use App\Models\User;
use Livewire\Livewire;

test('admins can open the facilities page', function () {
    $admin = User::factory()->admin()->create();
    Location::factory()->nameToBeConfirmed()->create(['name' => 'Midland']);

    $this->actingAs($admin)
        ->get(route('admin.facilities'))
        ->assertOk()
        ->assertSee('SignatureCare facilities')
        ->assertSee('Midland')
        ->assertSee('Name to be confirmed');
});

test('facility actions are forbidden for non-admins', function () {
    $manager = User::factory()->create();
    $location = Location::factory()->create();

    Livewire::actingAs($manager)
        ->test(LocationManagement::class)
        ->call('toggleActive', $location->id)
        ->assertForbidden();

    expect($location->refresh()->is_active)->toBeTrue();
});

test('admins can add a facility, and the state defaults to TX', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(LocationManagement::class)
        ->call('addFacility')
        ->assertSet('state', 'TX')
        ->set('name', ' Midland ')
        ->set('city', 'Midland')
        ->set('nameToBeConfirmed', true)
        ->call('saveFacility')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('locations', [
        'name' => 'Midland',
        'address' => null,
        'city' => 'Midland',
        'state' => 'TX',
        'is_active' => true,
        'name_to_be_confirmed' => true,
    ]);
});

test('admins can edit a facility', function () {
    $admin = User::factory()->admin()->create();
    $location = Location::factory()->nameToBeConfirmed()->create(['name' => 'Facility 6']);

    Livewire::actingAs($admin)
        ->test(LocationManagement::class)
        ->call('editFacility', $location->id)
        ->assertSet('name', 'Facility 6')
        ->assertSet('nameToBeConfirmed', true)
        ->set('name', 'Odessa')
        ->set('address', '1 Main St')
        ->set('state', 'ok')
        ->set('nameToBeConfirmed', false)
        ->call('saveFacility')
        ->assertHasNoErrors();

    $location->refresh();
    expect($location->name)->toBe('Odessa');
    expect($location->address)->toBe('1 Main St');
    expect($location->state)->toBe('OK');
    expect($location->name_to_be_confirmed)->toBeFalse();
});

test('a facility name must be unique', function () {
    $admin = User::factory()->admin()->create();
    Location::factory()->create(['name' => 'Frisco']);

    Livewire::actingAs($admin)
        ->test(LocationManagement::class)
        ->call('addFacility')
        ->set('name', 'Frisco')
        ->call('saveFacility')
        ->assertHasErrors(['name' => 'There is already a facility with that name.']);

    expect(Location::count())->toBe(1);
});

test('a facility keeps its own name when edited', function () {
    $admin = User::factory()->admin()->create();
    $location = Location::factory()->create(['name' => 'Frisco']);

    Livewire::actingAs($admin)
        ->test(LocationManagement::class)
        ->call('editFacility', $location->id)
        ->set('city', 'Frisco')
        ->call('saveFacility')
        ->assertHasNoErrors();
});

test('a facility needs a name and a two-letter state', function (string $state) {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(LocationManagement::class)
        ->call('addFacility')
        ->set('state', $state)
        ->call('saveFacility')
        ->assertHasErrors(['name' => 'required', 'state']);
})->with([
    'empty' => '',
    'too long' => 'TEX',
    'digits' => '12',
]);

test('admins can deactivate and reactivate a facility', function () {
    $admin = User::factory()->admin()->create();
    $location = Location::factory()->create();

    $component = Livewire::actingAs($admin)->test(LocationManagement::class);

    $component->call('toggleActive', $location->id);
    expect($location->refresh()->is_active)->toBeFalse();

    $component->call('toggleActive', $location->id);
    expect($location->refresh()->is_active)->toBeTrue();
});

test('the managers count only includes active facility managers who can inspect', function () {
    $admin = User::factory()->admin()->create();
    $location = Location::factory()->create();
    User::factory()->create()->locations()->attach($location, ['can_view' => true, 'can_inspect' => true]);
    User::factory()->create()->locations()->attach($location, ['can_view' => true, 'can_inspect' => false]);
    User::factory()->inactive()->create()->locations()->attach($location, ['can_view' => true, 'can_inspect' => true]);

    Livewire::actingAs($admin)
        ->test(LocationManagement::class)
        ->assertViewHas('locations', function ($locations) {
            return $locations->first()->managers_count === 1;
        });
});
