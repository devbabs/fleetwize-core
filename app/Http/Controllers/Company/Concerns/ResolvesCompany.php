<?php

namespace App\Http\Controllers\Company\Concerns;

use App\Models\Company;
use Illuminate\Http\Request;

/**
 * EnsureCompanyTenant already validated the subdomain against the
 * authenticated user's own company and stashed it on the request.
 *
 * Pitfall for every action on a route with a path parameter: the
 * `{company_slug}` domain segment is itself a route parameter, so
 * Laravel's positional dependency splicing shifts a scalar method
 * argument like `string $vehicle` to receive the company_slug value
 * instead. Read path parameters via $request->route('name') rather
 * than declaring them as method arguments.
 */
trait ResolvesCompany
{
    protected function currentCompany(Request $request): Company
    {
        return $request->attributes->get('company');
    }
}
