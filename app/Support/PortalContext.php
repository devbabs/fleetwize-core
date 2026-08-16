<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Workshop;
use Illuminate\Http\Request;

/**
 * Which portal the current user belongs to, derived from the request host
 * + the user's own roles — independent of which tenant middleware (if any)
 * actually ran for this specific route. EnsureCompanyTenant/EnsureWorkshopTenant
 * only populate their request attributes on routes gated by that exact
 * middleware, so portal-agnostic routes (like /settings/*) can't rely on
 * those attributes to know which sidebar the user came from. This gives
 * every page — gated or not — the same answer.
 */
class PortalContext
{
    /**
     * @return array{portal: string|null, company: Company|null, workshop: Workshop|null}
     */
    public static function resolve(Request $request): array
    {
        $none = ['portal' => null, 'company' => null, 'workshop' => null];

        $user = $request->user();

        if (! $user) {
            return $none;
        }

        $tenantDomain = config('fleetwize.tenant_domain');
        $host = $request->getHost();
        $onCompanySubdomain = $host !== $tenantDomain && str_ends_with($host, ".{$tenantDomain}");

        if ($onCompanySubdomain) {
            $companySlug = explode('.', $host)[0];
            $companyUser = $user->companyUser;

            if ($companyUser && $companyUser->company->slug === $companySlug) {
                if ($companyUser->role === 'workshop-admin' && $user->workshop) {
                    return ['portal' => 'workshop', 'company' => $companyUser->company, 'workshop' => $user->workshop];
                }

                if ($companyUser->role === 'admin') {
                    return ['portal' => 'company', 'company' => $companyUser->company, 'workshop' => null];
                }
            }

            return $none;
        }

        if ($user->admin) {
            return ['portal' => 'admin', 'company' => null, 'workshop' => null];
        }

        if ($user->agent) {
            return ['portal' => 'agent', 'company' => null, 'workshop' => null];
        }

        return $none;
    }
}
