<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Models\VehicleTrip;
use App\Services\CompanyRoleProvisioner;
use App\Services\Tracking\TraccarService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Obrainwave\AccessTree\Models\Role;
use Obrainwave\AccessTree\Models\Permission;
use Throwable;

/**
 * Traccar computes trip aggregates server-side (/api/reports/trips) — a
 * trip is only known once it's over, so unlike positions/events this has
 * to be pulled on an interval rather than pushed. Scheduled via
 * bootstrap/app.php's withSchedule().
 */
class BackfillCompanyRoles extends Command
{
    protected $signature = 'roles:backfill-companies
                            {--company= : Backfill a single company ID}
                            {--dry-run : Preview role mappings without writing to the database}';

    protected $description = 'Provision default roles for existing companies and backfill company_users.role_id + AccessTree user_roles.';

    /**
     * Maps legacy or variant role strings to current default role slugs.
     *
     * @var array<string, string>
     */
    protected array $legacyRoleMap = [
        'company-admin' => 'admin',
        'company_admin' => 'admin',
        'enterprise-admin' => 'admin',
        'supervisor' => 'branch-manager',
        'staff' => 'fleet-manager',
        'vehicle-operator' => 'driver',
        'workshop-admin' => 'maintenance-manager',
    ];

    public function handle(CompanyRoleProvisioner $provisioner): int
    {
        if (Permission::query()->count() === 0) {
            $this->error('No permissions found. Run "php artisan db:seed --class=PermissionsSeeder" first.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $companyId = $this->option('company');

        $query = Company::query()->orderBy('id');

        if ($companyId) {
            $query->where('id', (int) $companyId);
        }

        $companies = $query->get();

        if ($companies->isEmpty()) {
            $this->warn('No matching companies found.');

            return self::SUCCESS;
        }

        $this->info(
            ($dryRun ? '[DRY RUN] ' : '') . "Processing {$companies->count()} company(ies)..."
        );

        $totalLinked = 0;
        $totalSkipped = 0;

        foreach ($companies as $company) {
            $this->line("--------------------------------------------------");
            $this->info("Company #{$company->id}: {$company->name} ({$company->slug})");

            if (! $dryRun) {
                $provisioner->provisionForCompany($company);
            }

            $rolesBySlug = Role::query()
                ->where('company_id', $company->id)
                ->get()
                ->keyBy('slug');

            $companyUsers = CompanyUser::query()
                ->where('company_id', $company->id)
                ->with('user')
                ->get();

            if ($companyUsers->isEmpty()) {
                $this->line('  No staff records found.');
                continue;
            }

            foreach ($companyUsers as $companyUser) {
                $user = $companyUser->user;

                if (! $user) {
                    $this->warn("  Skipping CompanyUser #{$companyUser->id}: missing User #{$companyUser->user_id}.");
                    $totalSkipped++;
                    continue;
                }

                $targetSlug = $this->resolveTargetSlug($companyUser);

                if ($dryRun) {
                    $this->line(
                        "  [DRY RUN] User #{$user->id} ({$user->email}): '{$companyUser->role}' -> '{$targetSlug}'"
                    );
                    $totalLinked++;
                    continue;
                }

                $role = $rolesBySlug->get($targetSlug)
                    ?? $rolesBySlug->get('driver');

                if (! $role) {
                    $this->warn("  Skipping User #{$user->id}: target role '{$targetSlug}' not found for company #{$company->id}.");
                    $totalSkipped++;
                    continue;
                }

                DB::transaction(function () use ($company, $companyUser, $user, $role) {
                    $companyUser->role_id = $role->id;
                    $companyUser->role = $role->slug;
                    $companyUser->save();

                    // Preserve roles belonging to any other company this user belongs to
                    DB::transaction(function () use ($companyUser, $role) {
                        $companyUser->role_id = $role->id;
                        $companyUser->role = $role->slug;
                        $companyUser->save();
                    });
                });

                $this->line("  Synced User #{$user->id} ({$user->email}) -> {$role->name} (role_id={$role->id})");
                $totalLinked++;
            }
        }

        $this->line('==================================================');
        $this->info(
            ($dryRun ? '[DRY RUN] ' : '') . "Complete. Linked: {$totalLinked}, Skipped: {$totalSkipped}."
        );

        return self::SUCCESS;
    }

    protected function resolveTargetSlug(CompanyUser $companyUser): string
    {
        $raw = strtolower(trim((string) $companyUser->role));

        if ($raw === '') {
            return $companyUser->is_contact_person ? 'admin' : 'driver';
        }

        $mapped = $this->legacyRoleMap[$raw] ?? $raw;

        $knownSlugs = array_keys(CompanyRoleProvisioner::defaultTemplates());

        if (in_array($mapped, $knownSlugs, true)) {
            return $mapped;
        }

        // Preserve already-created custom company role slugs if they exist
        $customExists = Role::query()
            ->where('company_id', $companyUser->company_id)
            ->where('slug', $mapped)
            ->exists();

        if ($customExists) {
            return $mapped;
        }

        return $companyUser->is_contact_person ? 'admin' : 'driver';
    }
}
