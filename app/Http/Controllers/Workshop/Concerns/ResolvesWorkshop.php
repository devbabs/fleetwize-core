<?php

namespace App\Http\Controllers\Workshop\Concerns;

use App\Models\Company;
use App\Models\Workshop;
use Illuminate\Http\Request;

/**
 * EnsureWorkshopTenant already validated the workshop-admin against this
 * subdomain and stashed both the company and workshop on the request.
 */
trait ResolvesWorkshop
{
    protected function currentCompany(Request $request): Company
    {
        return $request->attributes->get('company');
    }

    protected function currentWorkshop(Request $request): Workshop
    {
        return $request->attributes->get('workshop');
    }
}
