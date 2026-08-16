<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the tracker-middleware webhook. Trackers/Traccar can't present a
 * Sanctum bearer token, so this checks a shared secret instead, sent by
 * Traccar's `forward.header` config as `X-Webhook-Secret: <value>`.
 */
class EnsureValidTrackerWebhookSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('fleetwize.traccar.webhook_secret');
        $provided = (string) $request->header('X-Webhook-Secret');

        if (! $expected || ! hash_equals((string) $expected, $provided)) {
            abort(401);
        }

        return $next($request);
    }
}
