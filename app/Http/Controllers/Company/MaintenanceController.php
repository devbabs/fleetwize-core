<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Models\MaintenanceAlert;
use App\Models\MaintenanceRecord;
use App\Models\VehicleServiceEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceController extends Controller
{
    use ResolvesCompany;

    // public function index(Request $request): Response
    // {
    //     $company = $this->currentCompany($request);

    //     $vehicles = $company->vehicles()
    //         ->with([
    //             'trackerState',
    //             'maintenanceSchedules' => fn ($query) => $query
    //                 ->where('active', true),

    //             'maintenanceRecords' => fn ($query) => $query
    //                 ->latest('maintained_at'),
    //         ])
    //         ->get();

    //     $overdue = collect();
    //     $upcoming = collect();

    //     foreach ($vehicles as $vehicle) {
    //         foreach ($vehicle->maintenanceSchedules as $schedule) {
    //             $latestRecord = $vehicle->maintenanceRecords
    //                 ->where('maintenance_schedule_id', $schedule->id)
    //                 ->sortByDesc('maintained_at')
    //                 ->first();

    //             $currentOdometer = $vehicle->trackerState?->odometer;

    //             /*
    //             * Use most recent maintenance record as the baseline.
    //             * If this schedule has never been serviced, use the
    //             * schedule's original baseline instead.
    //             */
    //             $lastMaintainedAt =
    //                 $latestRecord?->maintained_at
    //                 ?? $schedule->baseline_started_at;

    //             $lastOdometer =
    //                 $latestRecord?->odometer_km
    //                 ?? $schedule->baseline_odometer_km;

    //             $distanceDue = false;
    //             $timeDue = false;

    //             $distanceRemaining = null;
    //             $daysRemaining = null;

    //             $nextServiceOdometer = null;
    //             $nextServiceDate = null;

    //             /*
    //             * Odometer-based maintenance
    //             */
    //             if (
    //                 $schedule->distance_interval_km !== null &&
    //                 $currentOdometer !== null &&
    //                 $lastOdometer !== null
    //             ) {
    //                 $nextServiceOdometer =
    //                     $lastOdometer + $schedule->distance_interval_km;

    //                 $distanceRemaining =
    //                     $nextServiceOdometer - $currentOdometer;

    //                 $distanceDue =
    //                     $currentOdometer >= $nextServiceOdometer;
    //             }

    //             /*
    //             * Time-based maintenance
    //             */
    //             if (
    //                 $schedule->time_interval_days !== null &&
    //                 $lastMaintainedAt !== null
    //             ) {
    //                 $nextServiceDate = $lastMaintainedAt
    //                     ->copy()
    //                     ->addDays($schedule->time_interval_days);

    //                 $daysRemaining = now()->diffInDays(
    //                     $nextServiceDate,
    //                     false
    //                 );

    //                 $timeDue = now()->greaterThanOrEqualTo(
    //                     $nextServiceDate
    //                 );
    //             }

    //             $entry = [
    //                 'id' => $schedule->id,

    //                 'vehicle' =>
    //                     $vehicle->license_plate
    //                     ?? trim("{$vehicle->make} {$vehicle->model}"),

    //                 'vehicleId' => $vehicle->id,
    //                 'scheduleId' => $schedule->id,
    //                 'name' => $schedule->name,

    //                 'distanceIntervalKm' =>
    //                     $schedule->distance_interval_km,

    //                 'timeIntervalDays' =>
    //                     $schedule->time_interval_days,

    //                 'lastMaintainedAt' =>
    //                     $latestRecord?->maintained_at?->toDateString(),

    //                 'lastOdometer' => $lastOdometer,

    //                 'currentOdometer' => $currentOdometer,

    //                 'nextServiceOdometer' =>
    //                     $nextServiceOdometer,

    //                 'nextServiceDate' =>
    //                     $nextServiceDate?->toDateString(),

    //                 'distanceRemainingKm' =>
    //                     $distanceRemaining,

    //                 'daysRemaining' =>
    //                     $daysRemaining,

    //                 'status' => ($distanceDue || $timeDue)
    //                     ? 'overdue'
    //                     : 'upcoming',

    //                 'distanceDue' => $distanceDue,
    //                  'timeDue' => $timeDue,

    //                  'dueReason' => match (true) {
    //                     $distanceDue && $timeDue => 'distance_and_time',
    //                     $distanceDue => 'distance',
    //                     $timeDue => 'time',
    //                     default => null,
    //                 },
    //             ];

    //             if ($distanceDue || $timeDue) {
    //                 $overdue->push($entry);

    //                 continue;
    //             }

    //             $isUpcoming =
    //                 (
    //                     $daysRemaining !== null &&
    //                     $daysRemaining >= 0 &&
    //                     $daysRemaining <= 30
    //                 )
    //                 ||
    //                 (
    //                     $distanceRemaining !== null &&
    //                     $distanceRemaining >= 0 &&
    //                     $distanceRemaining <= 1000
    //                 );

    //             if ($isUpcoming) {
    //                 $upcoming->push($entry);
    //             }
    //         }
    //     }

    //     $history = MaintenanceRecord::query()
    //         ->whereIn(
    //             'vehicle_id',
    //             $vehicles->pluck('id')
    //         )
    //         ->with([
    //             'vehicle:id,license_plate,make,model',
    //             'maintenanceSchedule:id,name',
    //         ])
    //         ->latest('maintained_at')
    //         ->paginate(15)
    //         ->withQueryString()
    //         ->through(fn (MaintenanceRecord $record) => [
    //             'id' => $record->id,

    //             'vehicle' => $record->vehicle->license_plate
    //                 ?? trim(
    //                     "{$record->vehicle->make} {$record->vehicle->model}"
    //                 ),

    //             'vehicleId' => $record->vehicle_id,

    //             'scheduleId' => $record->maintenance_schedule_id,

    //             'name' => $record->maintenanceSchedule?->name,

    //             'maintainedAt' =>
    //                 $record->maintained_at?->toDateString(),

    //             'odometerKm' => $record->odometer_km,

    //             'notes' => $record->notes,
    //         ]);

    //     return Inertia::render('company/maintenance/index', [
    //         'overdue' => $overdue->values(),
    //         'upcoming' => $upcoming->values(),
    //         'history' => $history,
    //     ]);
    // }

    public function index(Request $request): Response
    {
        $company = $this->currentCompany($request);

        $vehicles = $company->vehicles()
            ->with([
                'trackerState',
                'maintenanceSchedules' => fn ($query) => $query
                    ->where('active', true),

                'maintenanceRecords' => fn ($query) => $query
                    ->latest('maintained_at'),
            ])
            ->get();

        $overdue = collect();
        $upcoming = collect();

        foreach ($vehicles as $vehicle) {
            foreach ($vehicle->maintenanceSchedules as $schedule) {
                $latestRecord = $vehicle->maintenanceRecords
                    ->where('maintenance_schedule_id', $schedule->id)
                    ->sortByDesc('maintained_at')
                    ->first();

                // Match CheckMaintenanceSchedules fallback
                $currentOdometer = $vehicle->trackerState?->odometer ?? $vehicle->mileage;

                /*
                * Use most recent maintenance record as the baseline.
                * If this schedule has never been serviced, use the
                * schedule's original baseline instead.
                */
                $lastMaintainedAt =
                    $latestRecord?->maintained_at
                    ?? $schedule->baseline_started_at;

                $lastOdometer =
                    $latestRecord?->odometer_km
                    ?? $schedule->baseline_odometer_km;

                $distanceDue = false;
                $timeDue = false;

                $distanceSinceService = null;
                $distanceRemaining = null;
                $daysRemaining = null;

                $nextServiceOdometer = null;
                $nextServiceDate = null;

                /*
                * Odometer-based maintenance
                * Spec formula: Distance Since Service = Current Odometer - Last Service Odometer
                */
                if (
                    $schedule->distance_interval_km !== null &&
                    $currentOdometer !== null &&
                    $lastOdometer !== null
                ) {
                    $distanceSinceService = max(0, (int) round($currentOdometer - $lastOdometer));

                    $nextServiceOdometer =
                        (int) round($lastOdometer + $schedule->distance_interval_km);

                    $distanceRemaining =
                        (int) round($nextServiceOdometer - $currentOdometer);

                    $distanceDue =
                        $distanceRemaining <= 0;
                }

                /*
                * Time-based maintenance
                */
                if (
                    $schedule->time_interval_days !== null &&
                    $lastMaintainedAt !== null
                ) {
                    $nextServiceDate = $lastMaintainedAt
                        ->copy()
                        ->addDays($schedule->time_interval_days);

                    $daysRemaining = (int) now()->diffInDays(
                        $nextServiceDate,
                        false
                    );

                    $timeDue = now()->greaterThanOrEqualTo(
                        $nextServiceDate
                    );
                }

                $entry = [
                    'id' => $schedule->id,

                    'vehicle' =>
                        $vehicle->license_plate
                        ?? trim("{$vehicle->make} {$vehicle->model}"),

                    'vehicleId' => $vehicle->id,
                    'scheduleId' => $schedule->id,
                    'name' => $schedule->name,

                    'distanceIntervalKm' =>
                        $schedule->distance_interval_km,

                    'timeIntervalDays' =>
                        $schedule->time_interval_days,

                    'lastMaintainedAt' =>
                        ($latestRecord?->maintained_at ?? $schedule->baseline_started_at)?->toDateString(),

                    'lastOdometer' => $lastOdometer !== null ? (int) round($lastOdometer) : null,

                    'currentOdometer' => $currentOdometer !== null ? (int) round($currentOdometer) : null,

                    'distanceSinceServiceKm' => $distanceSinceService,

                    'nextServiceOdometer' =>
                        $nextServiceOdometer,

                    'nextServiceDate' =>
                        $nextServiceDate?->toDateString(),

                    'distanceRemainingKm' =>
                        $distanceRemaining,

                    'daysRemaining' =>
                        $daysRemaining,

                    'status' => ($distanceDue || $timeDue)
                        ? 'overdue'
                        : 'upcoming',

                    'distanceDue' => $distanceDue,
                    'timeDue' => $timeDue,

                    'dueReason' => match (true) {
                        $distanceDue && $timeDue => 'distance_and_time',
                        $distanceDue => 'distance',
                        $timeDue => 'time',
                        default => null,
                    },
                ];

                if ($distanceDue || $timeDue) {
                    $overdue->push($entry);

                    continue;
                }

                // Updated from 1000 km to 500 km to match the meeting specification
                $isUpcoming =
                    (
                        $daysRemaining !== null &&
                        $daysRemaining >= 0 &&
                        $daysRemaining <= 14
                    )
                    ||
                    (
                        $distanceRemaining !== null &&
                        $distanceRemaining >= 0 &&
                        $distanceRemaining <= 500
                    );

                if ($isUpcoming) {
                    $upcoming->push($entry);
                }
            }
        }

        $history = MaintenanceRecord::query()
            ->whereIn(
                'vehicle_id',
                $vehicles->pluck('id')
            )
            ->with([
                'vehicle:id,license_plate,make,model',
                'maintenanceSchedule:id,name',
            ])
            ->latest('maintained_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (MaintenanceRecord $record) => [
                'id' => $record->id,

                'vehicle' => $record->vehicle->license_plate
                    ?? trim(
                        "{$record->vehicle->make} {$record->vehicle->model}"
                    ),

                'vehicleId' => $record->vehicle_id,

                'scheduleId' => $record->maintenance_schedule_id,

                'name' => $record->maintenanceSchedule?->name ?? 'General Service',

                'maintainedAt' =>
                    $record->maintained_at?->toDateString(),

                'odometerKm' => $record->odometer_km,

                'notes' => $record->notes,
            ]);

        return Inertia::render('company/maintenance/index', [
            'overdue' => $overdue->values(),
            'upcoming' => $upcoming->values(),
            'history' => $history,
        ]);
    }

    public function storeSchedule(Request $request): RedirectResponse
    {
        $company = $this->currentCompany($request);
        $vehicle = $company->vehicles()
            ->with('trackerState')
            ->findOrFail((string) $request->route('vehicle'));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'distance_interval_km' => ['nullable', 'integer', 'min:100', 'required_without:time_interval_days'],
            'time_interval_days' => ['nullable', 'integer', 'min:1', 'required_without:distance_interval_km'],
            'baseline_odometer_km' => ['nullable', 'integer', 'min:0'],
            'baseline_started_at' => ['nullable', 'date'],
        ]);

        // Fallback order: Admin input -> Live Tracker Odometer -> Vehicle static mileage -> 0
        $defaultBaselineOdometer = (int) round(
            $vehicle->trackerState?->odometer ?? $vehicle->mileage ?? 0
        );

        $vehicle->maintenanceSchedules()->create([
            'name' => $validated['name'],
            'distance_interval_km' => $validated['distance_interval_km'] ?? null,
            'time_interval_days' => $validated['time_interval_days'] ?? null,
            'baseline_odometer_km' => $validated['baseline_odometer_km'] ?? $defaultBaselineOdometer,
            'baseline_started_at' => isset($validated['baseline_started_at'])
                ? \Illuminate\Support\Carbon::parse($validated['baseline_started_at'])
                : now(),
            'active' => true,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Maintenance schedule '{$validated['name']}' configured.",
        ]);

        return back();
    }

    public function updateSchedule(Request $request): RedirectResponse
    {
        $company = $this->currentCompany($request);
        $vehicle = $company->vehicles()->findOrFail((string) $request->route('vehicle'));
        $schedule = $vehicle->maintenanceSchedules()->findOrFail((string) $request->route('schedule'));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'distance_interval_km' => ['nullable', 'integer', 'min:100'],
            'time_interval_days' => ['nullable', 'integer', 'min:1'],
            'baseline_odometer_km' => ['required', 'integer', 'min:0'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $schedule->update($validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Schedule '{$schedule->name}' updated.",
        ]);

        return back();
    }

    public function storeRecord(Request $request): RedirectResponse
    {
        $company = $this->currentCompany($request);
        $vehicle = $company->vehicles()->findOrFail((string) $request->route('vehicle'));

        $validated = $request->validate([
            'maintenance_schedule_id' => ['required', 'integer'],
            'maintained_at' => ['required', 'date'],
            'odometer_km' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Ensure the schedule belongs to this vehicle
        $schedule = $vehicle->maintenanceSchedules()->findOrFail($validated['maintenance_schedule_id']);

        $vehicle->maintenanceRecords()->create([
            'maintenance_schedule_id' => $schedule->id,
            'maintained_at' => $validated['maintained_at'],
            'odometer_km' => $validated['odometer_km'],
            'notes' => $validated['notes'] ?? null,
        ]);

        // Automatically acknowledge open alerts for this schedule
        MaintenanceAlert::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('maintenance_schedule_id', $schedule->id)
            ->where('acknowledged', false)
            ->update(['acknowledged' => true]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$schedule->name} logged as completed. Next interval reset.",
        ]);

        return back();
    }

    /** @return array<string, mixed> */
    protected function mapEntry(VehicleServiceEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'vehicle' => $entry->vehicle->license_plate ?? trim("{$entry->vehicle->make} {$entry->vehicle->model}"),
            'vehicleId' => $entry->vehicle_id,
            'startsAt' => $entry->starts_at?->toIso8601String(),
            'endsAt' => $entry->ends_at?->toIso8601String(),
            'comments' => $entry->comments,
            'tasks' => $entry->tasks->map(fn ($task) => $task->serviceTask->name)->filter()->values(),
        ];
    }
}
