<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\LogsOutUnauthorizedTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the /agent portal to users who own an Agent record.
 */
class EnsureUserIsAgent
{
    use LogsOutUnauthorizedTenant;

    public function handle(Request $request, Closure $next): Response
    {
        $agent = $request->user()?->agent;

        if (! $agent) {
            $this->denyAccess($request);
        }

        $request->attributes->set('agent', $agent);

        return $next($request);
    }
}
