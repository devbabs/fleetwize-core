<?php

use App\Http\Controllers\Webhooks\TrackerWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhooks
|--------------------------------------------------------------------------
|
| Server-to-server endpoints, not part of the `web` middleware group —
| no session, no CSRF. Authenticated by shared secret instead (see
| App\Http\Middleware\EnsureValidTrackerWebhookSecret).
|
*/

Route::post('webhooks/traccar/position', [TrackerWebhookController::class, 'position'])
    ->middleware(['throttle:120,1', 'tracker-webhook-secret'])
    ->name('webhooks.traccar.position');
