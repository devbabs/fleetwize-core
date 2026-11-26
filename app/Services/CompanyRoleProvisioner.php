<?php

namespace App\Services;

use App\Models\Company;
use App\Models\SystemLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Obrainwave\AccessTree\Models\Role;
use App\Models\Permission;

/**
 * On-demand REST pulls against the self-hosted Traccar instance — used for
 * an admin "force refresh" or backfilling state outside the normal webhook
 * flow (see App\Jobs\ProcessTrackerTelemetry for the primary ingestion path).
 */
class CompanyRoleProvisioner
{
    /**
     * Starter templates for newly created companies.
     * Companies can freely edit, rename, delete (except admin), or add custom roles.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function defaultTemplates(): array
    {
        return [
            'admin' => [
                'name' => 'Company Admin',
                'description' => 'Full control over fleet, staff, roles, billing, and settings.',
                'is_system' => true,
                'can_be_assigned_vehicle' => false,
                'permissions' => ['*'], // All permissions
            ],
            'fleet-manager' => [
                'name' => 'Fleet Manager',
                'description' => 'Day-to-day fleet monitoring, vehicle assignments, maintenance, fuel, and reporting.',
                'is_system' => false,
                'can_be_assigned_vehicle' => false,
                'permissions' => [
                    'overview.view', 'live_tracking.view', 'playback.view',
                    'vehicles.view', 'vehicles.create', 'vehicles.update', 'vehicles.import', 'vehicles.assign_driver', 'vehicles.send_command',
                    'documents.view', 'documents.create', 'documents.update',
                    'geofences.view', 'geofences.create', 'geofences.update', 'geofences.assign',
                    'alarms.view', 'alarms.acknowledge', 'alarms.share',
                    'maintenance.view', 'maintenance.manage_schedules', 'maintenance.log_service', 'maintenance.acknowledge_alerts',
                    'fuel.view', 'fuel.create', 'fuel.update',
                    'expenses.view', 'expenses.create', 'expenses.update',
                    'drivers.view', 'drivers.manage', 'scorecards.view',
                    'reports.view', 'reports.export',
                    'staff.view',
                ],
            ],
            'dispatcher' => [
                'name' => 'Dispatcher / Ops Controller',
                'description' => 'Tracks vehicles live, monitors routes/geofences, and manages trip operations.',
                'is_system' => false,
                'can_be_assigned_vehicle' => false,
                'permissions' => [
                    'overview.view', 'live_tracking.view', 'playback.view',
                    'vehicles.view', 'vehicles.assign_driver',
                    'geofences.view', 'alarms.view', 'alarms.acknowledge',
                    'drivers.view', 'scorecards.view', 'reports.view',
                ],
            ],
            'branch-manager' => [
                'name' => 'Regional / Branch Manager',
                'description' => 'Oversees branch fleet performance, compliance, and expenses.',
                'is_system' => false,
                'can_be_assigned_vehicle' => false,
                'permissions' => [
                    'overview.view', 'live_tracking.view', 'playback.view',
                    'vehicles.view', 'vehicles.update', 'vehicles.assign_driver',
                    'documents.view', 'documents.update',
                    'geofences.view', 'alarms.view', 'alarms.acknowledge',
                    'maintenance.view', 'fuel.view', 'fuel.create',
                    'expenses.view', 'expenses.create',
                    'drivers.view', 'scorecards.view', 'reports.view', 'reports.export',
                ],
            ],
            'control-room-officer' => [
                'name' => 'Control Room Officer',
                'description' => 'Monitors real-time telemetry, alarms, and escalates security incidents.',
                'is_system' => false,
                'can_be_assigned_vehicle' => false,
                'permissions' => [
                    'overview.view', 'live_tracking.view', 'playback.view',
                    'vehicles.view', 'geofences.view',
                    'alarms.view', 'alarms.acknowledge', 'alarms.share',
                ],
            ],
            'driver' => [
                'name' => 'Driver',
                'description' => 'Operates assigned fleet vehicles and logs fuel/check-ins.',
                'is_system' => false,
                'can_be_assigned_vehicle' => true,
                'permissions' => ['vehicles.view', 'fuel.create', 'scorecards.view'],
            ],
            'dispatch-rider' => [
                'name' => 'Dispatch Rider',
                'description' => 'Last-mile bike/delivery vehicle operator.',
                'is_system' => false,
                'can_be_assigned_vehicle' => true,
                'permissions' => ['vehicles.view', 'fuel.create', 'scorecards.view'],
            ],
            'maintenance-manager' => [
                'name' => 'Maintenance Manager',
                'description' => 'Manages service schedules, workshop records, and maintenance costs.',
                'is_system' => false,
                'can_be_assigned_vehicle' => false,
                'permissions' => [
                    'overview.view', 'vehicles.view', 'documents.view', 'documents.update',
                    'alarms.view', 'alarms.acknowledge',
                    'maintenance.view', 'maintenance.manage_schedules', 'maintenance.log_service', 'maintenance.acknowledge_alerts',
                    'expenses.view', 'expenses.create', 'expenses.update',
                    'workshop.view', 'workshop.log_diagnostics', 'reports.view',
                ],
            ],
            'mechanic' => [
                'name' => 'Mechanic / Vendor',
                'description' => 'Logs diagnostic reports and service completions.',
                'is_system' => false,
                'can_be_assigned_vehicle' => false,
                'permissions' => [
                    'vehicles.view', 'maintenance.view', 'maintenance.log_service',
                    'workshop.view', 'workshop.log_diagnostics',
                ],
            ],
            'tracker-installer' => [
                'name' => 'Tracker Installer',
                'description' => 'Onboards trackers, checks telemetry health, and runs diagnostics.',
                'is_system' => false,
                'can_be_assigned_vehicle' => false,
                'permissions' => [
                    'live_tracking.view', 'vehicles.view', 'vehicles.update',
                    'alarms.view', 'workshop.view',
                ],
            ],
        ];
    }

    public function provisionForCompany(Company $company): void
    {
        $allPermissions = Permission::query()->pluck('id', 'slug');

        DB::transaction(function () use ($company, $allPermissions) {
            foreach (self::defaultTemplates() as $slug => $config) {
                $role = Role::query()->firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'slug' => $slug,
                    ],
                    [
                        'name' => $config['name'],
                        'description' => $config['description'],
                        'is_system' => $config['is_system'],
                        'can_be_assigned_vehicle' => $config['can_be_assigned_vehicle'],
                        'status' => 1,
                    ]
                );

                $permissionIds = $config['permissions'] === ['*']
                    ? $allPermissions->values()->all()
                    : collect($config['permissions'])
                        ->map(fn (string $permSlug) => $allPermissions->get($permSlug))
                        ->filter()
                        ->values()
                        ->all();

                $role->permissions()->sync($permissionIds);
            }
        });
    }
}
