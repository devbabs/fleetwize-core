<?php

namespace App\Http\Responses;

use App\Support\PortalContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

/**
 * Fortify's default LoginResponse always sends everyone to
 * config('fortify.home') ('/dashboard'), which on a company subdomain
 * redirects to the admin-only /overview page. A workshop-admin (or agent,
 * or a mobile-only user with no web portal role) landing there gets
 * force-logged-out by that page's tenant middleware and bounced back to
 * /login — looking like a broken login. This picks the right home per role
 * instead of a single hardcoded destination.
 */
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        return redirect()->intended($this->destinationFor($request));
    }

    protected function destinationFor(Request $request): string
    {
        if (! $request->user()) {
            return '/login';
        }

        $portal = PortalContext::resolve($request)['portal'];

        return match ($portal) {
            'company' => '/overview',
            'workshop' => '/workshop/dashboard',
            'agent' => '/agent/dashboard',
            'admin' => '/admin/dashboard',
            default => $this->denyAndRedirectToLogin(
                $request,
                PortalContext::isCompanySubdomain($request)
                    ? 'Your account does not have access to this company portal.'
                    : 'This account does not have access to the web dashboard.',
            ),
        };
    }

    protected function denyAndRedirectToLogin(Request $request, string $message): string
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->flash('status', $message);

        return '/login';
    }
}
