<?php

use App\Enums\UserRole;
use App\Livewire\Admin\UserManagement;
use App\Models\Location;
use App\Models\User;
use Livewire\Livewire;

test('admin pages are forbidden for non-admins', function (UserRole $role, string $routeName) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)->get(route($routeName))->assertForbidden();
})->with([
    'facility manager' => UserRole::FacilityManager,
    'regional director' => UserRole::RegionalDirector,
])->with([
    'users page' => 'admin.users',
    'facilities page' => 'admin.facilities',
    'facility access page' => 'admin.access',
]);

test('admin pages redirect guests to the login page', function () {
    $this->get(route('admin.users'))->assertRedirect(route('login'));
});

test('admins can open the users page', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->create(['name' => 'Maria Gonzalez']);

    $this->actingAs($admin)
        ->get(route('admin.users'))
        ->assertOk()
        ->assertSee('Approved users')
        ->assertSee('Maria Gonzalez');
});

test('user actions are forbidden for non-admins', function () {
    $manager = User::factory()->create();

    Livewire::actingAs($manager)
        ->test(UserManagement::class)
        ->set('newName', 'Sam Rivera')
        ->set('newEmail', 'sam@ercare24.com')
        ->call('addUser')
        ->assertForbidden();

    $this->assertDatabaseMissing('users', ['email' => 'sam@ercare24.com']);
});

test('admins can add a user, and the email is lower-cased', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->set('newName', '  Sam Rivera ')
        ->set('newEmail', ' Sam.Rivera@ERCare24.com ')
        ->set('newRole', 'regional_director')
        ->call('addUser')
        ->assertHasNoErrors()
        ->assertSet('adding', false)
        ->assertSet('newEmail', '');

    $this->assertDatabaseHas('users', [
        'name' => 'Sam Rivera',
        'email' => 'sam.rivera@ercare24.com',
        'role' => UserRole::RegionalDirector->value,
        'is_active' => true,
    ]);
});

test('adding a user requires a name, an email and a valid role', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->set('newRole', 'owner')
        ->call('addUser')
        ->assertHasErrors(['newName' => 'required', 'newEmail' => 'required', 'newRole']);
});

test('adding an email that is already on the list is rejected', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'maria@ercare24.com']);

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->set('newName', 'Maria Again')
        ->set('newEmail', 'MARIA@ercare24.com')
        ->call('addUser')
        ->assertHasErrors(['newEmail' => 'That email is already on the list.']);

    expect(User::where('email', 'maria@ercare24.com')->count())->toBe(1);
});

test('changing a user email lower-cases it and unlinks their Google account', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->withGoogle()->create(['email' => 'old@ercare24.com']);

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('editUser', $manager->id)
        ->assertSet('editEmail', 'old@ercare24.com')
        ->set('editName', 'New Name')
        ->set('editEmail', 'New@ERCare24.com')
        ->call('updateUser')
        ->assertHasNoErrors();

    $manager->refresh();
    expect($manager->name)->toBe('New Name');
    expect($manager->email)->toBe('new@ercare24.com');
    expect($manager->google_id)->toBeNull();
});

test('editing only the name keeps the Google account linked', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->withGoogle()->create(['email' => 'maria@ercare24.com']);
    $googleId = $manager->google_id;

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('editUser', $manager->id)
        ->set('editName', 'Maria G.')
        ->call('updateUser')
        ->assertHasNoErrors();

    expect($manager->refresh()->google_id)->toBe($googleId);
});

test('a user email cannot be changed to one already on the list', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'taken@ercare24.com']);
    $manager = User::factory()->create(['email' => 'maria@ercare24.com']);

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('editUser', $manager->id)
        ->set('editEmail', 'taken@ercare24.com')
        ->call('updateUser')
        ->assertHasErrors(['editEmail' => 'That email is already on the list.']);

    expect($manager->refresh()->email)->toBe('maria@ercare24.com');
});

test('admins can change another user role', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('changeRole', $manager->id, 'regional_director');

    expect($manager->refresh()->role)->toBe(UserRole::RegionalDirector);
});

test('an admin cannot change their own role', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('changeRole', $admin->id, 'facility_manager');

    expect($admin->refresh()->role)->toBe(UserRole::Admin);
});

test('admins can turn another user off and on again', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->create();

    $component = Livewire::actingAs($admin)->test(UserManagement::class);

    $component->call('toggleActive', $manager->id);
    expect($manager->refresh()->is_active)->toBeFalse();

    $component->call('toggleActive', $manager->id);
    expect($manager->refresh()->is_active)->toBeTrue();
});

test('an admin cannot turn off their own access', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('toggleActive', $admin->id);

    expect($admin->refresh()->is_active)->toBeTrue();
});

test('the facilities column lists each manager facility and marks view-only ones', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->create();
    $manager->locations()->attach(Location::factory()->create(['name' => 'Frisco']), ['can_view' => true, 'can_inspect' => true]);
    $manager->locations()->attach(Location::factory()->create(['name' => 'Killeen']), ['can_view' => true, 'can_inspect' => false]);
    User::factory()->create();

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->assertSee('Frisco, Killeen (view)')
        ->assertSee('None yet: set in Facility access')
        ->assertSee('All facilities');
});
