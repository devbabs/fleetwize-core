<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $company = $request->attributes->get('company');
        $user = $request->user();

        if (! $company || ! $user) {
            abort(403);
        }

        foreach ($permissions as $permission) {
            if ($user->hasCompanyPermission($company, $permission)) {
                return $next($request);
            }
        }

        abort(403, 'You do not have permission to perform this action.');
    }
}
