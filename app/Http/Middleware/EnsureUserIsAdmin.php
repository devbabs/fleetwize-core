<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\LogsOutUnauthorizedTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the /admin portal to super-admin users only.
 */
class EnsureUserIsAdmin
{
    use LogsOutUnauthorizedTenant;

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->admin) {
            $this->denyAccess($request);
        }

        return $next($request);
    }
}
