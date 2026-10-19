<?php

use App\Models\User;

test('a deactivated user who is still signed in is signed out on their next page load', function () {
    $user = User::factory()->inactive()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['google' => 'Your account has been deactivated. Ask an ERCare24 admin for access.']);
    $this->assertGuest();
});

test('an active user stays signed in', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $this->assertAuthenticatedAs($user);
});
