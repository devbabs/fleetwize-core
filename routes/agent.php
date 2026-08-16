<?php

use App\Http\Controllers\Agent\CustomerController;
use App\Http\Controllers\Agent\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Agent Portal
|--------------------------------------------------------------------------
|
| Sales agents who onboard customers/vehicles remotely, gated by the
| `agent` middleware alias (App\Http\Middleware\EnsureUserIsAgent).
|
*/

Route::prefix('agent')->middleware(['auth', 'agent'])->name('agent.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::post('/customers/{customer}/vehicles', [CustomerController::class, 'addVehicle'])->name('customers.vehicles.store');
});
