<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Vehicle;
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
            'vehicles' => $company->vehicles()->select('id', 'license_plate', 'make', 'model')->get(),
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
            'fleet-summary'     => $this->exportFleetSummary($request, $company, $format),
            'trips'             => $this->exportTrips($request, $company, $format, $startDate, $endDate),
            'alarms'            => $this->exportAlarms($request, $company, $format, $startDate, $endDate),
            'driver-scorecard'  => $this->exportDriverScorecard($request, $company, $format, $startDate, $endDate),
            'idle-utilization'  => $this->exportIdleUtilization($request, $company, $format, $startDate, $endDate),
            default             => abort(404, "Report type '{$type}' not found"),
        };
    }

    protected function exportFleetSummary(Request $request, Company $company, string $format): HttpResponse
    {
        $vehicles = $this->filteredVehiclesQuery($request, $company)->get();

        if ($format === 'csv') {
            $writer = SimpleExcelWriter::streamDownload("{$company->slug}-fleet-summary.csv");
            foreach ($vehicles as $vehicle) {
                $writer->addRow([
                    'Plate' => $vehicle->license_plate,
                    'Make' => $vehicle->make,
                    'Model' => $vehicle->model,
                    'Category' => $vehicle->category,
                    'Status' => $vehicle->status,
                    'Mileage (km)' => round($vehicle->mileage, 1),
                    'Online' => $vehicle->trackerState?->isOnline() ? 'Yes' : 'No',
                    'Open Faults' => $vehicle->faults_count,
                ]);
            }
            return response()->stream(fn () => $writer->toBrowser(), 200);
        }

        return Pdf::view('reports.fleet-summary', [
            'company' => $company,
            'vehicles' => $vehicles,
            'generatedAt' => now(),
        ])
            ->format('a4')
            ->download("{$company->slug}-fleet-summary.pdf")
            ->toResponse($request);
    }

    protected function exportTrips(Request $request, Company $company, string $format, Carbon $start, Carbon $end): HttpResponse
    {
        $vehicles = $company->vehicles()->with([
            'trips' => function ($q) use ($start, $end) {
                $q->whereBetween('start_time', [$start, $end])
                  ->orderBy('start_time', 'desc');
            }
        ])
        ->when($request->filled('vehicle_id') && $request->input('vehicle_id') !== 'all', function ($q) use ($request) {
            $q->where('id', $request->input('vehicle_id'));
        })
        ->get();

        if ($format === 'csv') {
            $writer = SimpleExcelWriter::streamDownload("{$company->slug}-trips.csv");
            foreach ($vehicles as $vehicle) {
                foreach ($vehicle->trips as $trip) {
                    $writer->addRow([
                        'Plate' => $vehicle->license_plate,
                        'Driver' => $trip->driver_name ?? 'Unassigned',
                        'Start Time' => $trip->start_time?->toDateTimeString(),
                        'End Time' => $trip->end_time?->toDateTimeString(),
                        'Start Location' => $trip->start_address ?? ($trip->start_latitude ? "{$trip->start_latitude}, {$trip->start_longitude}" : '—'),
                        'End Location' => $trip->end_address ?? ($trip->end_latitude ? "{$trip->end_latitude}, {$trip->end_longitude}" : '—'),
                        'Distance (km)' => round($trip->distance_km ?? 0, 2),
                        'Duration (min)' => $trip->duration_seconds ? round($trip->duration_seconds / 60, 1) : 0,
                        'Max Speed (km/h)' => round($trip->max_speed_km_per_hr ?? 0, 1),
                        'Avg Speed (km/h)' => round($trip->average_speed_km_per_hr ?? 0, 1),
                    ]);
                }
            }
            return response()->stream(fn () => $writer->toBrowser(), 200);
        }

        return Pdf::view('reports.trips-summary', [
            'company' => $company,
            'vehicles' => $vehicles,
            'start' => $start,
            'end' => $end,
            'generatedAt' => now(),
        ])
            ->withBrowsershot(function ($browsershot) {
                $browsershot->noSandbox()
                            ->setOption('args', ['--disable-web-security', '--disable-setuid-sandbox']);
            })
            ->format('a4')
            ->download("{$company->slug}-trips.pdf")
            ->toResponse($request);
    }

    protected function exportAlarms(Request $request, Company $company, string $format, Carbon $start, Carbon $end): HttpResponse
    {
        $vehicles = $company->vehicles()->with([
            'alarms' => function ($q) use ($start, $end) {
                $q->whereBetween('gps_time', [$start, $end])
                  ->orderBy('gps_time', 'desc');
            }
        ])
        ->when($request->filled('vehicle_id') && $request->input('vehicle_id') !== 'all', function ($q) use ($request) {
            $q->where('id', $request->input('vehicle_id'));
        })
        ->get();

        if ($format === 'csv') {
            $writer = SimpleExcelWriter::streamDownload("{$company->slug}-alarms.csv");
            foreach ($vehicles as $vehicle) {
                foreach ($vehicle->alarms as $alarm) {
                    $writer->addRow([
                        'Plate' => $vehicle->license_plate,
                        'Type' => $alarm->alarm_type,
                        'Description' => $alarm->alarm_description ?? $alarm->description ?? 'Security Alarm',
                        'Timestamp' => $alarm->gps_time?->toDateTimeString(),
                        'Status' => $alarm->acknowledged_at ? 'Acknowledged' : 'Active',
                    ]);
                }
            }
            return response()->stream(fn () => $writer->toBrowser(), 200);
        }

        return Pdf::view('reports.alarms-summary', [
            'company' => $company,
            'vehicles' => $vehicles,
            'start' => $start,
            'end' => $end,
            'generatedAt' => now(),
        ])
            ->format('a4')
            ->download("{$company->slug}-alarms.pdf")
            ->toResponse($request);
    }

    protected function exportDriverScorecard(Request $request, Company $company, string $format, Carbon $start, Carbon $end): HttpResponse
    {
        $vehicles = $company->vehicles()
            ->with(['trackerState', 'trips' => fn ($q) => $q->whereBetween('start_time', [$start, $end])])
            ->when($request->filled('vehicle_id') && $request->input('vehicle_id') !== 'all', function ($q) use ($request) {
                $q->where('id', $request->input('vehicle_id'));
            })
            ->get();

        if ($format === 'csv') {
            $writer = SimpleExcelWriter::streamDownload("{$company->slug}-driver-scorecard.csv");
            foreach ($vehicles as $vehicle) {
                $state = $vehicle->trackerState;
                $writer->addRow([
                    'Plate' => $vehicle->license_plate,
                    'Trips Recorded' => $vehicle->trips->count(),
                    'Hard Acceleration Events' => $state?->hard_acceleration_count ?? 0,
                    'Hard Deceleration Events' => $state?->hard_deceleration_count ?? 0,
                    'Hard Cornering Events' => $state?->hard_cornering_count ?? 0,
                ]);
            }
            return response()->stream(fn () => $writer->toBrowser(), 200);
        }

        return Pdf::view('reports.driver-scorecard', [
            'company' => $company,
            'vehicles' => $vehicles,
            'start' => $start,
            'end' => $end,
            'generatedAt' => now(),
        ])
            ->format('a4')
            ->download("{$company->slug}-driver-scorecard.pdf")
            ->toResponse($request);
    }

    protected function exportIdleUtilization(Request $request, Company $company, string $format, Carbon $start, Carbon $end): HttpResponse
    {
        $vehicles = $company->vehicles()
            ->with(['trackerState', 'trips' => fn ($q) => $q->whereBetween('start_time', [$start, $end])])
            ->when($request->filled('vehicle_id') && $request->input('vehicle_id') !== 'all', function ($q) use ($request) {
                $q->where('id', $request->input('vehicle_id'));
            })
            ->get();

        if ($format === 'csv') {
            $writer = SimpleExcelWriter::streamDownload("{$company->slug}-idle-utilization.csv");
            foreach ($vehicles as $vehicle) {
                $totalDrivingSecs = $vehicle->trips->sum('duration_seconds');
                $writer->addRow([
                    'Plate' => $vehicle->license_plate,
                    'Engine Hours' => $vehicle->trackerState?->engine_hours ?? '—',
                    'Total Driving Hours' => round($totalDrivingSecs / 3600, 1),
                    'Total Distance (km)' => round($vehicle->trips->sum('distance_km'), 1),
                ]);
            }
            return response()->stream(fn () => $writer->toBrowser(), 200);
        }

        return Pdf::view('reports.idle-utilization', [
            'company' => $company,
            'vehicles' => $vehicles,
            'start' => $start,
            'end' => $end,
            'generatedAt' => now(),
        ])
            ->format('a4')
            ->download("{$company->slug}-idle-utilization.pdf")
            ->toResponse($request);
    }

    protected function filteredVehiclesQuery(Request $request, Company $company): Builder|Relation
    {
        return $company->vehicles()
            ->withCount(['faults' => fn ($q) => $q->whereNull('cleared_at')])
            ->with('trackerState')
            ->when($request->filled('category') && $request->input('category') !== 'all', fn ($q) => $q->where('category', $request->input('category')))
            ->when($request->filled('status') && $request->input('status') !== 'all', fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('connectivity') && $request->input('connectivity') !== 'all', function ($q) use ($request) {
                $q->whereHas('trackerState', fn ($ts) => $ts->where('is_online', $request->input('connectivity') === 'online'));
            })
            ->orderBy('license_plate');
    }
}