<?php

namespace App\Http\Middleware\Concerns;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Shared by the portal-gating middleware: an authenticated user who fails
 * the portal's role/tenant check is logged out rather than shown a 403,
 * since these portals are tenant-isolated and a mismatched session is
 * treated as a security event, not a permissions oversight.
 */
trait LogsOutUnauthorizedTenant
{
    protected function denyAccess(Request $request): never
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        throw new AuthenticationException;
    }
}
