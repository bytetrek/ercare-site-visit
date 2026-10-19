<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('admins see the Administration links in the sidebar', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Administration')
        ->assertSee(route('admin.users'));
});

test('non-admins do not see the Administration links in the sidebar', function () {
    $this->actingAs(User::factory()->regionalDirector()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Administration')
        ->assertDontSee(route('admin.users'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});
