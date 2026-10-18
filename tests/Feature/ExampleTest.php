<?php

use App\Models\User;

test('the home page shows the google sign-in button to guests', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee(route('auth.google.redirect'));
});

test('signed-in users visiting the home page are sent to the dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertRedirect(route('dashboard'));
});
