<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessTrackerTelemetry;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TrackerWebhookController extends Controller
{
    /**
     * Receives a single decoded position, forwarded by Traccar
     * (forward.type=json) after it parses a tracker's raw transmission.
     */
    public function position(Request $request): Response
    {
        $request->validate([
            'position' => ['required', 'array'],
            'position.latitude' => ['required', 'numeric'],
            'position.longitude' => ['required', 'numeric'],
            'device' => ['required', 'array'],
            'device.uniqueId' => ['required', 'string'],
        ]);

        ProcessTrackerTelemetry::dispatch(
            $request->array('position'),
            $request->array('device'),
        );

        return response()->noContent();
    }
}
