<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Models\VehicleFuelEntry;
use App\Models\VehicleServiceEntry;
use App\Models\VehicleTrip;
use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ReportController extends Controller
{
    use ResolvesCompany;

    public function index(Request $request): InertiaResponse
    {
        $company = $this->currentCompany($request);

        return Inertia::render('company/reports/index', [
            'categories' => $company->vehicles()->distinct()->whereNotNull('category')->orderBy('category')->pluck('category'),
            'vehicles' => $company->vehicles()->select('id', 'license_plate', 'make', 'model')->orderBy('license_plate')->get(),
        ]);
    }

    public function download(Request $request): HttpResponse
    {
        $company = $this->currentCompany($request);

        $type = (string) $request->route('type');
        $format = (string) $request->route('format');

        $startDate = $request->date('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->subDays(7)->startOfDay();
        $endDate = $request->date('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();

        return match ($type) {
            'fleet-summary'    => $this->exportFleetSummary($request, $company, $format),
            'trips'            => $this->exportTrips($request, $company, $format, $startDate, $endDate),
            'alarms'           => $this->exportAlarms($request, $company, $format, $startDate, $endDate),
            'driver-scorecard' => $this->exportDriverScorecard($request, $company, $format, $startDate, $endDate),
            'idle-utilization' => $this->exportIdleUtilization($request, $company, $format, $startDate, $endDate),
            'fuel'             => $this->exportFuel($request, $company, $format, $startDate, $endDate),
            'maintenance'      => $this->exportMaintenance($request, $company, $format),
            default            => abort(404, "Report type '{$type}' not found"),
        };
    }

    protected function exportFleetSummary(Request $request, Company $company, string $format): HttpResponse
    {
        $vehicles = $this->filteredVehiclesQuery($request, $company)->get();
        $activeAssignments = $this->loadActiveAssignments($vehicles->pluck('id'));

        if ($format === 'csv') {
            $writer = SimpleExcelWriter::streamDownload("{$company->slug}-fleet-summary.csv");
            foreach ($vehicles as $vehicle) {
                $state = $vehicle->trackerState;
                $writer->addRow([
                    'Plate' => $vehicle->license_plate,
                    'Make' => $vehicle->make,
                    'Model' => $vehicle->model,
                    'Category' => $vehicle->category ?? '—',
                    'Driver' => $activeAssignments->get($vehicle->id)?->companyUser?->user?->full_name ?? 'Unassigned',
                    'Status' => $vehicle->status,
                    'Online' => $state?->isOnline() ? 'Yes' : 'No',
                    'Odometer (km)' => round($vehicle->mileage ?? 0, 1),
                    'Fuel Level (%)' => $state?->fuel_level_percent !== null ? round($state->fuel_level_percent, 0) : '—',
                    'Battery (V)' => $state?->battery_voltage !== null ? round($state->battery_voltage, 1) : '—',
                    'Coolant (°C)' => $state?->coolant_temp !== null ? round($state->coolant_temp, 0) : '—',
                    'Open Faults' => $vehicle->faults_count,
                ]);
            }
            return response()->stream(fn () => $writer->toBrowser(), 200);
        }

        $vehicles->each(function ($vehicle) use ($activeAssignments) {
            $vehicle->current_driver_name = $activeAssignments->get($vehicle->id)?->companyUser?->user?->full_name;
        });

        return DomPdf::loadView('reports.fleet-summary', [
            'company' => $company,
            'vehicles' => $vehicles,
            'generatedAt' => now(),
        ])
            ->setPaper('a4', 'landscape')
            ->download("{$company->slug}-fleet-summary.pdf");
    }

    protected function exportTrips(Request $request, Company $company, string $format, Carbon $start, Carbon $end): HttpResponse
    {
        $vehicles = $this->baseVehiclesQuery($request, $company)
            ->with([
                'trips' => function ($q) use ($start, $end) {
                    $q->whereBetween('start_time', [$start, $end])
                        ->with('companyUser.user')
                        ->orderBy('start_time', 'desc');
                },
            ])
            ->get();

        $activeAssignments = $this->loadActiveAssignments($vehicles->pluck('id'));

        if ($format === 'csv') {
            $writer = SimpleExcelWriter::streamDownload("{$company->slug}-trips.csv");
            foreach ($vehicles as $vehicle) {
                $fallbackDriver = $activeAssignments->get($vehicle->id)?->companyUser?->user?->full_name;

                foreach ($vehicle->trips as $trip) {
                    $driverName = $trip->companyUser?->user?->full_name
                        ?? $trip->driver_name
                        ?? $fallbackDriver
                        ?? 'Unassigned';

                    $writer->addRow([
                        'Plate' => $vehicle->license_plate,
                        'Driver' => $driverName,
                        'Start Time' => $trip->start_time?->toDateTimeString(),
                        'End Time' => $trip->end_time?->toDateTimeString(),
                        'Start Location' => $trip->start_address ?? ($trip->start_latitude ? "{$trip->start_latitude}, {$trip->start_longitude}" : '—'),
                        'End Location' => $trip->end_address ?? ($trip->end_latitude ? "{$trip->end_latitude}, {$trip->end_longitude}" : '—'),
                        'Distance (km)' => round($trip->distance_km ?? 0, 2),
                        'Duration (min)' => $trip->duration_seconds ? round($trip->duration_seconds / 60, 1) : 0,
                        'Idle (min)' => (int) ($trip->idle_minutes ?? 0),
                        'Avg Speed (km/h)' => round($trip->average_speed_km_per_hr ?? 0, 1),
                        'Max Speed (km/h)' => round($trip->max_speed_km_per_hr ?? 0, 1),
                        'Score' => $trip->score !== null ? round((float) $trip->score, 1) : '—',
                        'Grade' => $trip->grade ?? '—',
                    ]);
                }
            }
            return response()->stream(fn () => $writer->toBrowser(), 200);
        }

        $vehicles->each(function ($vehicle) use ($activeAssignments) {
            $vehicle->current_driver_name = $activeAssignments->get($vehicle->id)?->companyUser?->user?->full_name;
        });

        return DomPdf::loadView('reports.trips-summary', [
            'company' => $company,
            'vehicles' => $vehicles,
            'start' => $start,
            'end' => $end,
            'generatedAt' => now(),
        ])
            ->setPaper('a4', 'portrait')
            ->download("{$company->slug}-trips.pdf");
    }

    protected function exportAlarms(Request $request, Company $company, string $format, Carbon $start, Carbon $end): HttpResponse
    {
        $vehicles = $this->baseVehiclesQuery($request, $company)
            ->with([
                'alarms' => function ($q) use ($start, $end) {
                    $q->whereBetween('gps_time', [$start, $end])
                        ->orderBy('gps_time', 'desc');
                },
            ])
            ->get();

        if ($format === 'csv') {
            $writer = SimpleExcelWriter::streamDownload("{$company->slug}-alarms.csv");
            foreach ($vehicles as $vehicle) {
                foreach ($vehicle->alarms as $alarm) {
                    $writer->addRow([
                        'Plate' => $vehicle->license_plate,
                        'Type' => $alarm->alarm_type,
                        'Severity' => ucfirst($alarm->severity ?? 'info'),
                        'Description' => $alarm->alarm_description ?? $alarm->description ?? 'Security Alarm',
                        'Timestamp' => $alarm->gps_time?->toDateTimeString(),
                        'Status' => $alarm->acknowledged_at ? 'Acknowledged' : 'Active',
                    ]);
                }
            }
            return response()->stream(fn () => $writer->toBrowser(), 200);
        }

        return DomPdf::loadView('reports.alarms-summary', [
            'company' => $company,
            'vehicles' => $vehicles,
            'start' => $start,
            'end' => $end,
            'generatedAt' => now(),
        ])
            ->setPaper('a4', 'portrait')
            ->download("{$company->slug}-alarms.pdf");
    }

    protected function exportDriverScorecard(Request $request, Company $company, string $format, Carbon $start, Carbon $end): HttpResponse
    {
        $vehicleIds = $this->baseVehiclesQuery($request, $company)->pluck('id');

        $trips = VehicleTrip::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->whereBetween('start_time', [$start, $end])
            ->whereNotNull('score_calculated_at')
            ->with(['vehicle:id,license_plate,make,model', 'companyUser.user'])
            ->get();

        // Group by driver (or vehicle plate if trip is unassigned)
        $scorecards = $trips
            ->groupBy(fn (VehicleTrip $t) => $t->company_user_id ? "driver-{$t->company_user_id}" : "vehicle-{$t->vehicle_id}")
            ->map(function ($group) {
                $first = $group->first();
                $totalDistance = round($group->sum(fn ($t) => (float) ($t->distance_km ?? 0)), 1);

                $brakingScore = round((float) $group->avg('harsh_braking_score'), 1);
                $accelScore = round((float) $group->avg('harsh_acceleration_score'), 1);
                $speedScore = round((float) $group->avg('speeding_score'), 1);
                $cornerScore = round((float) $group->avg('hard_cornering_score'), 1);
                $idleScore = round((float) $group->avg('idling_score'), 1);
                $overall = round(($brakingScore + $accelScore + $speedScore + $cornerScore + $idleScore) / 5, 1);

                return [
                    'driver_name' => $first->companyUser?->user?->full_name ?? $first->driver_name ?? 'Unassigned',
                    'plate' => $group->pluck('vehicle.license_plate')->filter()->unique()->implode(', '),
                    'trip_count' => $group->count(),
                    'distance_km' => $totalDistance,
                    'score' => $overall,
                    'grade' => match (true) {
                        $overall >= 90 => 'A',
                        $overall >= 80 => 'B',
                        $overall >= 70 => 'C',
                        $overall >= 60 => 'D',
                        default => 'F',
                    },
                    'harsh_braking_score' => $brakingScore,
                    'harsh_braking_events' => (int) $group->sum('harsh_braking_events'),
                    'harsh_acceleration_score' => $accelScore,
                    'harsh_acceleration_events' => (int) $group->sum('harsh_acceleration_events'),
                    'speeding_score' => $speedScore,
                    'overspeed_events' => (int) $group->sum('overspeed_events'),
                    'hard_cornering_score' => $cornerScore,
                    'harsh_cornering_events' => (int) $group->sum('harsh_cornering_events'),
                    'idling_score' => $idleScore,
                    'idle_minutes' => (int) $group->sum('idle_minutes'),
                ];
            })
            ->sortByDesc('score')
            ->values();

        if ($format === 'csv') {
            $writer = SimpleExcelWriter::streamDownload("{$company->slug}-driver-scorecard.csv");
            foreach ($scorecards as $row) {
                $writer->addRow([
                    'Driver' => $row['driver_name'],
                    'Vehicle(s)' => $row['plate'],
                    'Trips' => $row['trip_count'],
                    'Distance (km)' => $row['distance_km'],
                    'Overall Score' => $row['score'],
                    'Grade' => $row['grade'],
                    'Braking Score' => $row['harsh_braking_score'],
                    'Harsh Braking Events' => $row['harsh_braking_events'],
                    'Accel Score' => $row['harsh_acceleration_score'],
                    'Harsh Accel Events' => $row['harsh_acceleration_events'],
                    'Speeding Score' => $row['speeding_score'],
                    'Overspeed Events' => $row['overspeed_events'],
                    'Cornering Score' => $row['hard_cornering_score'],
                    'Hard Cornering Events' => $row['harsh_cornering_events'],
                    'Idling Score' => $row['idling_score'],
                    'Idle Minutes' => $row['idle_minutes'],
                ]);
            }
            return response()->stream(fn () => $writer->toBrowser(), 200);
        }

        return DomPdf::loadView('reports.driver-scorecard', [
            'company' => $company,
            'scorecards' => $scorecards,
            'start' => $start,
            'end' => $end,
            'generatedAt' => now(),
        ])
            ->setPaper('a4', 'landscape')
            ->download("{$company->slug}-driver-scorecard.pdf");
    }

    protected function exportIdleUtilization(Request $request, Company $company, string $format, Carbon $start, Carbon $end): HttpResponse
    {
        $vehicles = $this->baseVehiclesQuery($request, $company)
            ->with([
                'trackerState',
                'trips' => fn ($q) => $q->whereBetween('start_time', [$start, $end]),
            ])
            ->get();

        $rows = $vehicles->map(function (Vehicle $vehicle) {
            $drivingMins = round(($vehicle->trips->sum('duration_seconds') ?? 0) / 60, 1);
            $idleMins = (int) ($vehicle->trips->sum('idle_minutes') ?? 0);
            $totalRuntimeMins = $drivingMins + $idleMins;
            $idlePct = $totalRuntimeMins > 0 ? round(($idleMins / $totalRuntimeMins) * 100, 1) : 0.0;
            $estIdleFuelLiters = round(($idleMins / 60) * 1.5, 2); // ~1.5 L/hr average idle burn

            return [
                'vehicle' => $vehicle,
                'plate' => $vehicle->license_plate,
                'model' => "{$vehicle->make} {$vehicle->model}",
                'trips_count' => $vehicle->trips->count(),
                'distance_km' => round((float) $vehicle->trips->sum('distance_km'), 1),
                'driving_hours' => round($drivingMins / 60, 1),
                'idle_minutes' => $idleMins,
                'idle_pct' => $idlePct,
                'est_idle_fuel_l' => $estIdleFuelLiters,
            ];
        });

        if ($format === 'csv') {
            $writer = SimpleExcelWriter::streamDownload("{$company->slug}-idle-utilization.csv");
            foreach ($rows as $row) {
                $writer->addRow([
                    'Plate' => $row['plate'],
                    'Vehicle' => $row['model'],
                    'Trips' => $row['trips_count'],
                    'Distance (km)' => $row['distance_km'],
                    'Driving Hours' => $row['driving_hours'],
                    'Idle Minutes' => $row['idle_minutes'],
                    'Idle %' => "{$row['idle_pct']}%",
                    'Est. Idle Fuel Waste (L)' => $row['est_idle_fuel_l'],
                ]);
            }
            return response()->stream(fn () => $writer->toBrowser(), 200);
        }

        return DomPdf::loadView('reports.idle-utilization', [
            'company' => $company,
            'vehicles' => $vehicles,
            'rows' => $rows,
            'start' => $start,
            'end' => $end,
            'generatedAt' => now(),
        ])
            ->setPaper('a4', 'portrait')
            ->download("{$company->slug}-idle-utilization.pdf");
    }

    protected function exportFuel(Request $request, Company $company, string $format, Carbon $start, Carbon $end): HttpResponse
    {
        $vehicleIds = $this->baseVehiclesQuery($request, $company)->pluck('id');

        $entries = VehicleFuelEntry::query()
            ->where('company_id', $company->id)
            ->whereIn('vehicle_id', $vehicleIds)
            ->whereBetween('date', [$start, $end])
            ->with('vehicle:id,license_plate,make,model')
            ->latest('date')
            ->get();

        if ($format === 'csv') {
            $writer = SimpleExcelWriter::streamDownload("{$company->slug}-fuel.csv");
            foreach ($entries as $entry) {
                $writer->addRow([
                    'Date' => $entry->date?->toDateString(),
                    'Plate' => $entry->vehicle?->license_plate ?? '—',
                    'Vehicle' => trim(($entry->vehicle?->make ?? '') . ' ' . ($entry->vehicle?->model ?? '')),
                    'Litres' => round((float) $entry->litres, 2),
                    'Price / Litre (NGN)' => round((float) $entry->price_per_litre, 2),
                    'Total Cost (NGN)' => round((float) $entry->litres * (float) $entry->price_per_litre, 2),
                    'Odometer (km)' => $entry->meter_reading ?? '—',
                ]);
            }
            return response()->stream(fn () => $writer->toBrowser(), 200);
        }

        return DomPdf::loadView('reports.fuel-summary', [
            'company' => $company,
            'entries' => $entries,
            'start' => $start,
            'end' => $end,
            'generatedAt' => now(),
        ])
            ->setPaper('a4', 'portrait')
            ->download("{$company->slug}-fuel.pdf");
    }

    protected function exportMaintenance(Request $request, Company $company, string $format): HttpResponse
    {
        $vehicles = $this->baseVehiclesQuery($request, $company)
            ->withCount(['faults' => fn ($q) => $q->whereNull('cleared_at')])
            ->with('trackerState')
            ->get();

        $serviceEntries = VehicleServiceEntry::query()
            ->where('company_id', $company->id)
            ->whereIn('vehicle_id', $vehicles->pluck('id'))
            ->latest('starts_at')
            ->get()
            ->groupBy('vehicle_id');

        if ($format === 'csv') {
            $writer = SimpleExcelWriter::streamDownload("{$company->slug}-maintenance.csv");
            foreach ($vehicles as $vehicle) {
                $latestService = $serviceEntries->get($vehicle->id)?->first();
                $writer->addRow([
                    'Plate' => $vehicle->license_plate,
                    'Vehicle' => "{$vehicle->make} {$vehicle->model}",
                    'Category' => $vehicle->category ?? '—',
                    'Current Odometer (km)' => round($vehicle->mileage ?? 0, 1),
                    'Open DTC Faults' => $vehicle->faults_count,
                    'Last / Next Service' => $latestService?->starts_at?->toDateString() ?? 'None scheduled',
                    'Service Title' => $latestService?->title ?? '—',
                ]);
            }
            return response()->stream(fn () => $writer->toBrowser(), 200);
        }

        return DomPdf::loadView('reports.maintenance-summary', [
            'company' => $company,
            'vehicles' => $vehicles,
            'serviceEntries' => $serviceEntries,
            'generatedAt' => now(),
        ])
            ->setPaper('a4', 'portrait')
            ->download("{$company->slug}-maintenance.pdf");
    }

    protected function baseVehiclesQuery(Request $request, Company $company): Builder|Relation
    {
        return $company->vehicles()
            ->when($request->filled('vehicle_id') && $request->input('vehicle_id') !== 'all', fn ($q) => $q->where('id', $request->input('vehicle_id')))
            ->when($request->filled('category') && $request->input('category') !== 'all', fn ($q) => $q->where('category', $request->input('category')))
            ->orderBy('license_plate');
    }

    protected function filteredVehiclesQuery(Request $request, Company $company): Builder|Relation
    {
        return $this->baseVehiclesQuery($request, $company)
            ->withCount(['faults' => fn ($q) => $q->whereNull('cleared_at')])
            ->with('trackerState')
            ->when($request->filled('status') && $request->input('status') !== 'all', fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('connectivity') && $request->input('connectivity') !== 'all', function ($q) use ($request) {
                $q->whereHas('trackerState', fn ($ts) => $ts->where('is_online', $request->input('connectivity') === 'online'));
            });
    }

    protected function loadActiveAssignments($vehicleIds)
    {
        return VehicleAssignment::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
            })
            ->with('companyUser.user')
            ->latest('start_date')
            ->get()
            ->keyBy('vehicle_id');
    }
}