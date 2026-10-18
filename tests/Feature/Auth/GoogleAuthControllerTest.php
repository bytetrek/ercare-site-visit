<?php

use App\Models\User;
use Illuminate\Support\Facades\Exceptions;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as GoogleUser;

test('guests are redirected to google to sign in', function () {
    Socialite::fake('google');

    $response = $this->get(route('auth.google.redirect'));

    $response->assertRedirect('https://socialite.fake/google/authorize');
});

test('a whitelisted user signs in and their google account is linked', function () {
    $user = User::factory()->create(['email' => 'manager@ercare24.com']);

    Socialite::fake('google', GoogleUser::fake([
        'id' => 'google-123',
        'name' => 'Jane Manager',
        'email' => 'manager@ercare24.com',
        'avatar' => 'https://example.com/jane.jpg',
        'email_verified' => true,
    ]));

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'google_id' => 'google-123',
        'name' => 'Jane Manager',
        'avatar' => 'https://example.com/jane.jpg',
    ]);
});

test('a returning user keeps their name but gets a fresh avatar', function () {
    $user = User::factory()->create([
        'name' => 'Name Set By Admin',
        'email' => 'manager@ercare24.com',
        'google_id' => 'google-123',
    ]);

    Socialite::fake('google', GoogleUser::fake([
        'id' => 'google-123',
        'name' => 'Name From Google',
        'email' => 'manager@ercare24.com',
        'avatar' => 'https://example.com/new-photo.jpg',
        'email_verified' => true,
    ]));

    $this->get(route('auth.google.callback'));

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Name Set By Admin',
        'avatar' => 'https://example.com/new-photo.jpg',
    ]);
});

test('the email from google is matched without caring about upper or lower case', function () {
    $user = User::factory()->create(['email' => 'manager@ercare24.com']);

    Socialite::fake('google', GoogleUser::fake([
        'email' => 'Manager@ERCare24.com',
        'email_verified' => true,
    ]));

    $this->get(route('auth.google.callback'));

    $this->assertAuthenticatedAs($user);
});

test('an email that was not added by an admin cannot sign in', function () {
    Socialite::fake('google', GoogleUser::fake([
        'email' => 'stranger@gmail.com',
        'email_verified' => true,
    ]));

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['google' => 'This Google account is not on the approved list. Ask an ERCare24 admin to add your email.']);
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'stranger@gmail.com']);
});

test('a deactivated user cannot sign in', function () {
    User::factory()->inactive()->create(['email' => 'former@ercare24.com']);

    Socialite::fake('google', GoogleUser::fake([
        'email' => 'former@ercare24.com',
        'email_verified' => true,
    ]));

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['google' => 'This Google account is not on the approved list. Ask an ERCare24 admin to add your email.']);
    $this->assertGuest();
});

test('a google account with an unverified email cannot sign in', function () {
    User::factory()->create(['email' => 'manager@ercare24.com']);

    Socialite::fake('google', GoogleUser::fake([
        'email' => 'manager@ercare24.com',
        'email_verified' => false,
    ]));

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['google' => 'Your Google email address is not verified.']);
    $this->assertGuest();
});

test('a different google account cannot use an email that is already linked', function () {
    User::factory()->create([
        'email' => 'manager@ercare24.com',
        'google_id' => 'google-123',
    ]);

    Socialite::fake('google', GoogleUser::fake([
        'id' => 'google-999',
        'email' => 'manager@ercare24.com',
        'email_verified' => true,
    ]));

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['google' => 'This Google account is not on the approved list. Ask an ERCare24 admin to add your email.']);
    $this->assertGuest();
    $this->assertDatabaseHas('users', [
        'email' => 'manager@ercare24.com',
        'google_id' => 'google-123',
    ]);
});

test('the user is sent back to the login page when google sign-in fails', function () {
    Exceptions::fake();

    Socialite::fake('google', function () {
        throw new InvalidStateException;
    });

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['google' => 'Google sign-in did not complete. Please try again.']);
    $this->assertGuest();
    Exceptions::assertReported(InvalidStateException::class);
});
