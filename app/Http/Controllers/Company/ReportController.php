<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
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
            'categories' => $company->vehicles()->distinct()->orderBy('category')->pluck('category'),
        ]);
    }

    public function vehiclesPdf(Request $request): HttpResponse
    {
        $company = $this->currentCompany($request);
        $vehicles = $this->filteredVehicles($request, $company);

        return Pdf::view('reports.fleet-summary', [
            'company' => $company,
            'vehicles' => $vehicles,
            'generatedAt' => now(),
        ])
            ->driver('dompdf')
            ->format('a4')
            ->download("{$company->slug}-fleet-summary.pdf")
            ->toResponse($request);
    }

    public function vehiclesCsv(Request $request): void
    {
        $company = $this->currentCompany($request);
        $vehicles = $this->filteredVehicles($request, $company);

        $writer = SimpleExcelWriter::streamDownload("{$company->slug}-vehicles.csv");

        foreach ($vehicles as $vehicle) {
            $writer->addRow([
                'Plate' => $vehicle->license_plate,
                'Make' => $vehicle->make,
                'Model' => $vehicle->model,
                'Category' => $vehicle->category,
                'Status' => $vehicle->status,
                'Mileage (km)' => $vehicle->mileage,
                'Online' => $vehicle->trackerState?->isOnline() ? 'Yes' : 'No',
                'Open Faults' => $vehicle->faults_count,
            ]);
        }

        $writer->toBrowser();
    }

    /** @return Collection<int, Vehicle> */
    protected function filteredVehicles(Request $request, Company $company): Collection
    {
        $query = $company->vehicles()
            ->withCount(['faults' => fn ($query) => $query->whereNull('cleared_at')])
            ->with('trackerState')
            ->orderBy('license_plate');

        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        /** @var Collection<int, Vehicle> $vehicles */
        $vehicles = $query->get();

        if ($connectivity = $request->string('connectivity')->toString()) {
            $vehicles = $vehicles->filter(
                fn (Vehicle $vehicle) => $connectivity === 'online'
                    ? ($vehicle->trackerState?->isOnline() ?? false)
                    : ! ($vehicle->trackerState?->isOnline() ?? false)
            )->values();
        }

        return $vehicles;
    }
}
