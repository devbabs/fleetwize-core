<?php

use App\Models\Company;
use App\Models\User;
use App\Support\CompanyMembership;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| /broadcasting/auth is a structurally separate code path from normal
| page routes — EnsureCompanyTenant never runs on it — so this closure is
| the only thing standing between one company's users and another
| company's live vehicle data. It shares its authorization rule with
| EnsureCompanyTenant via App\Support\CompanyMembership.
|
*/

Broadcast::channel('company.{company}.vehicles', function (?User $user, Company $company) {
    return CompanyMembership::isCompanyAdmin($user, $company);
});
