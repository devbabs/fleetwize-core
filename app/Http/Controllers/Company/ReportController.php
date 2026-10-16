<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use App\Services\TraccarService;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\SimpleExcel\SimpleExcelWriter;

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

    public function download(Request $request, string $type, string $format): HttpResponse
    {
        $company = $this->currentCompany($request);
        $startDate = $request->date('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->subDays(7)->startOfDay();
        $endDate = $request->date('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();

        return match ($type) {
            'fleet-summary' => $this->exportFleetSummary($request, $company, $format),
            'trips'         => $this->exportTrips($request, $company, $format, $startDate, $endDate),
            'alarms'        => $this->exportAlarms($request, $company, $format, $startDate, $endDate),
            default         => abort(404, 'Report type not found'),
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
        ])->format('a4')->download("{$company->slug}-fleet-summary.pdf");
    }

    protected function exportTrips(Request $request, Company $company, string $format, Carbon $start, Carbon $end): HttpResponse
    {
        // Query normalized trips synced from Traccar
        $trips = $company->trips()
            ->with(['vehicle', 'driver'])
            ->whereBetween('started_at', [$start, $end])
            ->when($request->filled('vehicle_id') && $request->input('vehicle_id') !== 'all', function (Builder $q) use ($request) {
                $q->where('vehicle_id', $request->input('vehicle_id'));
            })
            ->get();

        if ($format === 'csv') {
            $writer = SimpleExcelWriter::streamDownload("{$company->slug}-trips.csv");
            foreach ($trips as $trip) {
                $writer->addRow([
                    'Plate' => $trip->vehicle?->license_plate,
                    'Driver' => $trip->driver?->name ?? 'Unassigned',
                    'Start Time' => $trip->started_at->toDateTimeString(),
                    'End Time' => $trip->ended_at?->toDateTimeString(),
                    'Start Location' => $trip->start_address,
                    'End Location' => $trip->end_address,
                    'Distance (km)' => round($trip->distance_meters / 1000, 2),
                    'Duration (min)' => round($trip->duration_seconds / 60, 1),
                    'Max Speed (km/h)' => round($trip->max_speed_knots * 1.852, 1),
                    'Avg Speed (km/h)' => round($trip->avg_speed_knots * 1.852, 1),
                ]);
            }
            return response()->stream(fn () => $writer->toBrowser(), 200);
        }

        return Pdf::view('reports.trips-summary', [
            'company' => $company,
            'trips' => $trips,
            'start' => $start,
            'end' => $end,
        ])->format('a4')->download("{$company->slug}-trips.pdf");
    }

    protected function filteredVehiclesQuery(Request $request, Company $company): Builder
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
