<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\LogsOutUnauthorizedTenant;
use App\Support\CompanyMembership;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the {company_slug}.{tenant_domain} company dashboard to that
 * company's admin user. Cross-checking the route's company_slug against
 * the authenticated user's own company stops a company-admin from one
 * tenant reaching another tenant's subdomain even by guessing the URL.
 * The "is this user an admin of this company" rule itself lives in
 * CompanyMembership, shared with the broadcasting channel authorization
 * in routes/channels.php.
 */
class EnsureCompanyTenant
{
    use LogsOutUnauthorizedTenant;

    public function handle(Request $request, Closure $next): Response
    {
        $company = $request->user()?->companyUser?->company;

        if (
            ! $company
            || ! CompanyMembership::isCompanyAdmin($request->user(), $company)
            || $company->slug !== $request->route('company_slug')
        ) {
            $this->denyAccess($request);
        }

        $request->attributes->set('company', $company);

        return $next($request);
    }
}
