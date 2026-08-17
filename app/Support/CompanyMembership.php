<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;

/**
 * The single place "is this user an admin of this company" is decided —
 * shared by EnsureCompanyTenant (HTTP route gating) and the broadcasting
 * channel authorization in routes/channels.php, which is a structurally
 * separate code path (/broadcasting/auth) that HTTP middleware never runs
 * on. Keeping both checks backed by this one helper stops them silently
 * drifting apart.
 */
class CompanyMembership
{
    public static function isCompanyAdmin(?User $user, Company $company): bool
    {
        $companyUser = $user?->companyUser;

        return $companyUser
            && $companyUser->role === 'admin'
            && $companyUser->company_id === $company->id;
    }
}
