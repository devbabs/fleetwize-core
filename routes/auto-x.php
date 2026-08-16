<?php

use App\Http\Controllers\Api\AutoX\AuthController;
use App\Http\Controllers\Api\AutoX\ProfileController;
use App\Http\Controllers\Api\AutoX\VehicleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API (Fleetwize app)
|--------------------------------------------------------------------------
|
| Bearer-token API for the Fleetwize mobile app, under api/auto-x — no
| session, no CSRF (the `api` middleware group only). Vehicles are
| provisioned server-side (agent/company onboarding), not self-service
| here, so this is read-only for vehicles.
|
*/

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('vehicles', [VehicleController::class, 'index']);
    Route::get('vehicles/{vehicle}', [VehicleController::class, 'show']);
    Route::get('vehicles/{vehicle}/live-state', [VehicleController::class, 'liveState']);

    Route::get('profile', [ProfileController::class, 'show']);
    Route::patch('profile', [ProfileController::class, 'update']);
    Route::post('profile/avatar', [ProfileController::class, 'updateAvatar']);
    Route::put('profile/password', [ProfileController::class, 'updatePassword']);
});
