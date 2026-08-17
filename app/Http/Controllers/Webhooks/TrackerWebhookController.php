<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessTrackerEvent;
use App\Jobs\ProcessTrackerTelemetry;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

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

    /**
     * Receives a single event, forwarded by Traccar's event.forward — a
     * separate mechanism from position forward, firing on ignitionOn/Off,
     * deviceMoving/Stopped, alarm, etc. Only the minimum safe shape is
     * validated here; event.* sub-fields aren't validated yet since the
     * exact real payload shape hasn't been confirmed (see the temporary
     * log line below and TraccarPayloadNormalizer::normalizeEvent()).
     */
    public function event(Request $request): Response
    {
        $request->validate([
            'event' => ['required', 'array'],
            'device' => ['required', 'array'],
            'device.uniqueId' => ['required', 'string'],
        ]);

        // TODO: remove once a real event.forward payload has been captured
        // and TraccarPayloadNormalizer::normalizeEvent()'s field mapping is
        // confirmed correct against it.
        Log::info('Raw Traccar event payload', $request->only(['event', 'device']));

        ProcessTrackerEvent::dispatch(
            $request->array('event'),
            $request->array('device'),
        );

        return response()->noContent();
    }
}
