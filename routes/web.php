<?php

use App\Livewire\Auth\InvitedUserRegister;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/user_register', InvitedUserRegister::class)
        ->name('invited.register');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Route::get('/app')->name('dashboard');
});

require __DIR__ . '/settings.php';
