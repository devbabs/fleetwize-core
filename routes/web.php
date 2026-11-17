<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

Route::post('/vehicles/{vehicle}/command', [\App\Http\Controllers\Company\VehicleController::class, 'sendCommand'])
    ->name('company.vehicles.command');

require __DIR__.'/settings.php';
