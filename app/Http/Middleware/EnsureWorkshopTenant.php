<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\LogsOutUnauthorizedTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates {company_slug}.{tenant_domain}/workshop to that company's
 * workshop-admin user, mirroring EnsureCompanyTenant's subdomain
 * cross-check but for the workshop-admin role/record instead.
 */
class EnsureWorkshopTenant
{
    use LogsOutUnauthorizedTenant;

    public function handle(Request $request, Closure $next): Response
    {
        $companyUser = $request->user()?->companyUser;
        $workshop = $request->user()?->workshop;

        if (
            ! $companyUser
            || ! $workshop
            || $companyUser->role !== 'workshop-admin'
            || $companyUser->company->slug !== $request->route('company_slug')
        ) {
            $this->denyAccess($request);
        }

        $request->attributes->set('company', $companyUser->company);
        $request->attributes->set('workshop', $workshop);

        return $next($request);
    }
}
