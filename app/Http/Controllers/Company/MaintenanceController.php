<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Models\MaintenanceRecord;
use App\Models\VehicleServiceEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceController extends Controller
{
    use ResolvesCompany;

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

                $currentOdometer = $vehicle->trackerState?->odometer;

                $lastMaintainedAt = $latestRecord?->maintained_at;
                $lastOdometer = $latestRecord?->odometer_km;

                $distanceDue = false;
                $timeDue = false;

                $distanceRemaining = null;
                $daysRemaining = null;

                /*
                 * Distance-based maintenance
                 */
                if (
                    $schedule->distance_interval_km !== null &&
                    $currentOdometer !== null &&
                    $lastOdometer !== null
                ) {
                    $distanceTravelled = $currentOdometer - $lastOdometer;

                    $distanceRemaining =
                        $schedule->distance_interval_km - $distanceTravelled;

                    $distanceDue = $distanceTravelled >=
                        $schedule->distance_interval_km;
                }

                /*
                 * Time-based maintenance
                 */
                if (
                    $schedule->time_interval_days !== null &&
                    $lastMaintainedAt !== null
                ) {
                    $daysElapsed = $lastMaintainedAt->diffInDays(now());

                    $daysRemaining =
                        $schedule->time_interval_days - $daysElapsed;

                    $timeDue = $daysElapsed >=
                        $schedule->time_interval_days;
                }

                /*
                 * If there has never been a maintenance record,
                 * we don't have a baseline for calculating an interval.
                 */
                if (! $latestRecord) {
                    continue;
                }

                $entry = [
                    'id' => $schedule->id,
                    'vehicle' => $vehicle->license_plate
                        ?? trim("{$vehicle->make} {$vehicle->model}"),
                    'vehicleId' => $vehicle->id,
                    'scheduleId' => $schedule->id,
                    'name' => $schedule->name,

                    'distanceIntervalKm' =>
                        $schedule->distance_interval_km,

                    'timeIntervalDays' =>
                        $schedule->time_interval_days,

                    'lastMaintainedAt' =>
                        $lastMaintainedAt?->toDateString(),

                    'lastOdometer' => $lastOdometer,

                    'currentOdometer' => $currentOdometer,

                    'distanceRemainingKm' => $distanceRemaining,

                    'daysRemaining' => $daysRemaining,

                    'status' => ($distanceDue || $timeDue)
                        ? 'overdue'
                        : 'upcoming',
                ];

                if ($distanceDue || $timeDue) {
                    $overdue->push($entry);
                } else {
                    /*
                     * Only show maintenance that is within
                     * the next 30 days / reasonable distance window.
                     */
                    $isUpcoming =
                        ($daysRemaining !== null && $daysRemaining <= 30)
                        || ($distanceRemaining !== null && $distanceRemaining <= 1000);

                    if ($isUpcoming) {
                        $upcoming->push($entry);
                    }
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

                'name' => $record->maintenanceSchedule?->name,

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
