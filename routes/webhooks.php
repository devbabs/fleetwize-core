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

// Traccar's event.forward — a separate mechanism from position forward
// (forward.url), configured independently in traccar.xml. Same secret,
// same middleware — it only checks the header, not the payload shape.
Route::post('webhooks/traccar/event', [TrackerWebhookController::class, 'event'])
    ->middleware(['throttle:120,1', 'tracker-webhook-secret'])
    ->name('webhooks.traccar.event');
