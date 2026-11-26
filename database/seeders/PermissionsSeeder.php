<?php

namespace Database\Seeders;

use App\Models\Document;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    /**
     * Complete catalog of actionable system permissions grouped by module.
     *
     * @return list<array{slug: string, name: string, module: string, description: string}>
     */
    public static function catalog(): array
    {
        return [
            // Overview & Telemetry
            [
                'slug' => 'overview.view',
                'name' => 'View Overview Dashboard',
                'module' => 'overview',
                'description' => 'Access fleet KPIs, active alert summaries, and upcoming service cards.',
            ],
            [
                'slug' => 'live_tracking.view',
                'name' => 'View Live Tracking',
                'module' => 'overview',
                'description' => 'Monitor live vehicle positions and real-time telemetry on the map.',
            ],
            [
                'slug' => 'playback.view',
                'name' => 'View Trip Playback',
                'module' => 'overview',
                'description' => 'Replay historical trip routes, speed curves, and route breadcrumbs.',
            ],

            // Vehicles
            [
                'slug' => 'vehicles.view',
                'name' => 'View Vehicles',
                'module' => 'vehicles',
                'description' => 'View vehicle list, profiles, telemetry details, and trip history.',
            ],
            [
                'slug' => 'vehicles.create',
                'name' => 'Create Vehicles',
                'module' => 'vehicles',
                'description' => 'Add new vehicles and attach tracker IMEIs.',
            ],
            [
                'slug' => 'vehicles.update',
                'name' => 'Update Vehicles',
                'module' => 'vehicles',
                'description' => 'Edit vehicle metadata, plates, speed limits, and tracker bindings.',
            ],
            [
                'slug' => 'vehicles.delete',
                'name' => 'Delete Vehicles',
                'module' => 'vehicles',
                'description' => 'Remove vehicles from the fleet.',
            ],
            [
                'slug' => 'vehicles.import',
                'name' => 'Bulk Import Vehicles',
                'module' => 'vehicles',
                'description' => 'Download templates and bulk-onboard vehicles via CSV.',
            ],
            [
                'slug' => 'vehicles.assign_driver',
                'name' => 'Assign Drivers to Vehicles',
                'module' => 'vehicles',
                'description' => 'Link and unlink eligible drivers or dispatch riders to vehicles.',
            ],
            [
                'slug' => 'vehicles.send_command',
                'name' => 'Send Remote Tracker Commands',
                'module' => 'vehicles',
                'description' => 'Execute remote commands such as immobilize, restore, or diagnostic scans.',
            ],

            // Vehicle Documents
            [
                'slug' => 'documents.view',
                'name' => 'View Vehicle Documents',
                'module' => 'documents',
                'description' => 'View vehicle compliance documents and expiry statuses.',
            ],
            [
                'slug' => 'documents.create',
                'name' => 'Add Vehicle Documents',
                'module' => 'documents',
                'description' => 'Upload and record new compliance documents for vehicles.',
            ],
            [
                'slug' => 'documents.update',
                'name' => 'Update / Renew Documents',
                'module' => 'documents',
                'description' => 'Edit document details and update renewal/expiry dates.',
            ],
            [
                'slug' => 'documents.delete',
                'name' => 'Delete Vehicle Documents',
                'module' => 'documents',
                'description' => 'Remove compliance document records.',
            ],

            // Geofences
            [
                'slug' => 'geofences.view',
                'name' => 'View Geofences',
                'module' => 'geofences',
                'description' => 'View configured zones and vehicle geofence assignments.',
            ],
            [
                'slug' => 'geofences.create',
                'name' => 'Create Geofences',
                'module' => 'geofences',
                'description' => 'Draw and save new polygon or radius geofences.',
            ],
            [
                'slug' => 'geofences.update',
                'name' => 'Update Geofences',
                'module' => 'geofences',
                'description' => 'Modify geofence boundaries, names, and breach rules.',
            ],
            [
                'slug' => 'geofences.delete',
                'name' => 'Delete Geofences',
                'module' => 'geofences',
                'description' => 'Delete geofences and remove them from Traccar.',
            ],
            [
                'slug' => 'geofences.assign',
                'name' => 'Assign Geofences to Vehicles',
                'module' => 'geofences',
                'description' => 'Link or unlink vehicles to geofence monitoring rules.',
            ],

            // Alarms & Faults
            [
                'slug' => 'alarms.view',
                'name' => 'View Alarms & Faults',
                'module' => 'alarms',
                'description' => 'View telemetry alarms, geofence breaches, and OBD diagnostic faults.',
            ],
            [
                'slug' => 'alarms.acknowledge',
                'name' => 'Acknowledge / Clear Alarms',
                'module' => 'alarms',
                'description' => 'Mark active alarms and diagnostic faults as acknowledged or resolved.',
            ],
            [
                'slug' => 'alarms.share',
                'name' => 'Share Alarm Reports',
                'module' => 'alarms',
                'description' => 'Export or share incident and alarm reports externally.',
            ],

            // Maintenance
            [
                'slug' => 'maintenance.view',
                'name' => 'View Maintenance',
                'module' => 'maintenance',
                'description' => 'View overdue schedules, upcoming services, and service history.',
            ],
            [
                'slug' => 'maintenance.manage_schedules',
                'name' => 'Manage Maintenance Schedules',
                'module' => 'maintenance',
                'description' => 'Create, edit, or deactivate distance/time maintenance schedules.',
            ],
            [
                'slug' => 'maintenance.log_service',
                'name' => 'Log Completed Services',
                'module' => 'maintenance',
                'description' => 'Record completed maintenance jobs and reset interval baselines.',
            ],
            [
                'slug' => 'maintenance.acknowledge_alerts',
                'name' => 'Acknowledge Maintenance Alerts',
                'module' => 'maintenance',
                'description' => 'Dismiss or acknowledge due-soon and overdue maintenance alerts.',
            ],

            // Fuel
            [
                'slug' => 'fuel.view',
                'name' => 'View Fuel Logs',
                'module' => 'fuel',
                'description' => 'View fuel entries, total litres, and fuel spend analytics.',
            ],
            [
                'slug' => 'fuel.create',
                'name' => 'Log Fuel Entries',
                'module' => 'fuel',
                'description' => 'Record vehicle refueling entries and receipts.',
            ],
            [
                'slug' => 'fuel.update',
                'name' => 'Update Fuel Entries',
                'module' => 'fuel',
                'description' => 'Edit previously recorded fuel logs.',
            ],
            [
                'slug' => 'fuel.delete',
                'name' => 'Delete Fuel Entries',
                'module' => 'fuel',
                'description' => 'Remove fuel log records.',
            ],

            // Expenses
            [
                'slug' => 'expenses.view',
                'name' => 'View Expenses',
                'module' => 'expenses',
                'description' => 'View operating expenses, total spend KPIs, and vendor costs.',
            ],
            [
                'slug' => 'expenses.create',
                'name' => 'Log Expenses',
                'module' => 'expenses',
                'description' => 'Record repairs, tolls, renewals, insurance, and other fleet expenses.',
            ],
            [
                'slug' => 'expenses.update',
                'name' => 'Update Expenses',
                'module' => 'expenses',
                'description' => 'Edit logged expense records.',
            ],
            [
                'slug' => 'expenses.delete',
                'name' => 'Delete Expenses',
                'module' => 'expenses',
                'description' => 'Remove expense records.',
            ],

            // Drivers & Scorecards
            [
                'slug' => 'drivers.view',
                'name' => 'View Drivers & Check-ins',
                'module' => 'drivers',
                'description' => 'View driver profiles, vehicle assignments, and daily check-ins.',
            ],
            [
                'slug' => 'drivers.manage',
                'name' => 'Manage Drivers',
                'module' => 'drivers',
                'description' => 'Update driver assignments and operational driver details.',
            ],
            [
                'slug' => 'scorecards.view',
                'name' => 'View Driver Scorecards',
                'module' => 'drivers',
                'description' => 'Access 5-pillar driver safety rankings and trip score breakdowns.',
            ],

            // Reports
            [
                'slug' => 'reports.view',
                'name' => 'View Reports',
                'module' => 'reports',
                'description' => 'Access fleet mileage, travel summary, fuel, and alarm reports.',
            ],
            [
                'slug' => 'reports.export',
                'name' => 'Export Reports (PDF / CSV)',
                'module' => 'reports',
                'description' => 'Download and export PDF and CSV fleet reports.',
            ],

            // Staff & Access Control
            [
                'slug' => 'staff.view',
                'name' => 'View Company Staff',
                'module' => 'staff',
                'description' => 'View company staff directory and role allocations.',
            ],
            [
                'slug' => 'staff.create',
                'name' => 'Add Company Staff',
                'module' => 'staff',
                'description' => 'Invite or create new staff accounts and assign roles.',
            ],
            [
                'slug' => 'staff.update',
                'name' => 'Update Company Staff',
                'module' => 'staff',
                'description' => 'Edit staff details and reassign company roles.',
            ],
            [
                'slug' => 'staff.delete',
                'name' => 'Remove Company Staff',
                'module' => 'staff',
                'description' => 'Deactivate or remove staff members from the company.',
            ],
            [
                'slug' => 'roles.view',
                'name' => 'View Roles & Permissions',
                'module' => 'roles',
                'description' => 'Inspect company roles and their assigned permissions.',
            ],
            [
                'slug' => 'roles.create',
                'name' => 'Create Roles',
                'module' => 'roles',
                'description' => 'Create new custom company roles.',
            ],
            [
                'slug' => 'roles.update',
                'name' => 'Update Roles',
                'module' => 'roles',
                'description' => 'Edit role names, vehicle-assignment flags, and permissions.',
            ],
            [
                'slug' => 'roles.delete',
                'name' => 'Delete Roles',
                'module' => 'roles',
                'description' => 'Delete unassigned custom company roles.',
            ],

            // Audit & Settings
            [
                'slug' => 'system_logs.view',
                'name' => 'View System Audit Logs',
                'module' => 'settings',
                'description' => 'Inspect the company activity audit trail.',
            ],
            [
                'slug' => 'company_settings.manage',
                'name' => 'Manage Company Settings',
                'module' => 'settings',
                'description' => 'Update company profile, notification preferences, and billing settings.',
            ],

            // Workshop Portal
            [
                'slug' => 'workshop.view',
                'name' => 'Access Workshop Portal',
                'module' => 'workshop',
                'description' => 'View workshop vehicle inspection queue and vehicle profiles.',
            ],
            [
                'slug' => 'workshop.log_diagnostics',
                'name' => 'Log Workshop Diagnostics',
                'module' => 'workshop',
                'description' => 'Submit diagnostic fault reports and service assessments.',
            ],
        ];
    }

    public function run(): void
    {
        foreach (self::catalog() as $item) {
            Permission::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'module' => $item['module'],
                    'description' => $item['description'],
                    'status' => 1,
                ]
            );
        }

        $this->command?->info('Seeded ' . count(self::catalog()) . ' system permissions.');
    }
}
