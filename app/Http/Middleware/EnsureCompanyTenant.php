<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\LogsOutUnauthorizedTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the {company_slug}.{tenant_domain} company dashboard to that
 * company's admin user. Cross-checking the route's company_slug against
 * the authenticated user's own company stops a company-admin from one
 * tenant reaching another tenant's subdomain even by guessing the URL.
 */
class EnsureCompanyTenant
{
    use LogsOutUnauthorizedTenant;

    public function handle(Request $request, Closure $next): Response
    {
        $companyUser = $request->user()?->companyUser;

        if (
            ! $companyUser
            || $companyUser->role !== 'admin'
            || $companyUser->company->slug !== $request->route('company_slug')
        ) {
            $this->denyAccess($request);
        }

        $request->attributes->set('company', $companyUser->company);

        return $next($request);
    }
}
