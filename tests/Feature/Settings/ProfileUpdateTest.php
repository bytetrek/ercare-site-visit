<?php

use App\Livewire\Settings\Profile;
use App\Models\User;
use Livewire\Livewire;

test('profile page is displayed', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/settings/profile')->assertOk();
});

test('users can change their name', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(Profile::class)
        ->set('name', 'Test User')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->name)->toEqual('Test User');
});

test('users can not change their own email', function () {
    $user = User::factory()->create(['email' => 'manager@ercare24.com']);

    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('email', 'someone-else@gmail.com')
        ->call('updateProfileInformation');

    expect($user->refresh()->email)->toEqual('manager@ercare24.com');
});
