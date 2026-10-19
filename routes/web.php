<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Livewire\Admin\LocationManagement;
use App\Livewire\Admin\LocationPermissions;
use App\Livewire\Admin\UserManagement;
use Illuminate\Support\Facades\Route;

// Only for guests (not logged in). Logged-in users are sent to the dashboard.
Route::middleware('guest')->group(function () {
    // The home page is the login page.
    Route::view('/', 'livewire.auth.login')->name('home');

    // Google sign-in
    Route::get('auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

// Administration pages, for admins only.
Route::middleware(['auth', 'verified', 'can:manage-users'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('users', UserManagement::class)->name('users');
    Route::livewire('facilities', LocationManagement::class)->name('facilities');
    Route::livewire('facility-access', LocationPermissions::class)->name('access');
});

require __DIR__.'/settings.php';
