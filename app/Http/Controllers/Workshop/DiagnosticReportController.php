<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Workshop\Concerns\ResolvesWorkshop;
use App\Models\VehicleDiagnosticReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelPdf\Facades\Pdf;

class DiagnosticReportController extends Controller
{
    use ResolvesWorkshop;

    public function index(Request $request): Response
    {
        $workshop = $this->currentWorkshop($request);

        $reports = $workshop->diagnosticReports()
            ->with(['vehicle:id,license_plate,make,model', 'createdBy:id,first_name,last_name'])
            ->withCount('faults')
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (VehicleDiagnosticReport $report) => [
                'id' => $report->id,
                'reference' => $report->reference,
                'vehicle' => $report->vehicle->license_plate ?? '—',
                'faultsCount' => $report->faults_count,
                'createdBy' => $report->createdBy->full_name,
                'createdAt' => $report->created_at?->toIso8601String(),
            ]);

        return Inertia::render('workshop/reports/index', [
            'reports' => $reports,
        ]);
    }

    public function create(Request $request): Response
    {
        $company = $this->currentCompany($request);

        return Inertia::render('workshop/reports/create', [
            'vehicleOptions' => $company->vehicles()
                ->orderBy('license_plate')
                ->get(['id', 'license_plate', 'make', 'model'])
                ->map(fn ($vehicle) => [
                    'id' => $vehicle->id,
                    'label' => trim(($vehicle->license_plate ?? '').' '.collect([$vehicle->make, $vehicle->model])->filter()->implode(' ')),
                ]),
            'preselectedVehicleId' => $request->integer('vehicle_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->currentCompany($request);
        $workshop = $this->currentWorkshop($request);

        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer', Rule::exists('vehicles', 'id')->where('company_id', $company->id)],
            'reference' => ['nullable', 'string', 'max:100'],
            'faults' => ['required', 'array', 'min:1'],
            'faults.*.severity' => ['required', Rule::in(['low', 'medium', 'major', 'critical'])],
            'faults.*.error_code' => ['nullable', 'string', 'max:50'],
            'faults.*.description' => ['nullable', 'string', 'max:1000'],
            'faults.*.remark' => ['nullable', 'string', 'max:1000'],
        ]);

        $report = new VehicleDiagnosticReport;
        $report->vehicle_id = $validated['vehicle_id'];
        $report->workshop_id = $workshop->id;
        $report->created_by_id = $request->user()->id;
        $report->reference = $validated['reference'] ?? null;
        $report->save();

        foreach ($validated['faults'] as $fault) {
            $report->faults()->create([
                'severity' => $fault['severity'],
                'error_code' => $fault['error_code'] ?? null,
                'description' => $fault['description'] ?? null,
                'remark' => $fault['remark'] ?? null,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Diagnostic report created.']);

        // A relative redirect (rather than to_route) sidesteps generating a
        // URL for a domain-scoped route, which would also need company_slug.
        return redirect("/workshop/reports/{$report->id}");
    }

    public function show(Request $request): Response
    {
        $workshop = $this->currentWorkshop($request);

        $report = $workshop->diagnosticReports()
            ->with(['vehicle', 'createdBy', 'faults'])
            ->findOrFail((string) $request->route('report'));

        return Inertia::render('workshop/reports/show', [
            'report' => $this->mapReport($report),
        ]);
    }

    public function pdf(Request $request): HttpResponse
    {
        $workshop = $this->currentWorkshop($request);

        $report = $workshop->diagnosticReports()
            ->with(['vehicle', 'createdBy', 'faults'])
            ->findOrFail((string) $request->route('report'));

        return Pdf::view('reports.diagnostic-report', [
            'workshop' => $workshop,
            'report' => $report,
            'generatedAt' => now(),
        ])
            ->driver('dompdf')
            ->format('a4')
            ->download('diagnostic-report-'.($report->reference ?: $report->id).'.pdf')
            ->toResponse($request);
    }

    /** @return array<string, mixed> */
    protected function mapReport(VehicleDiagnosticReport $report): array
    {
        return [
            'id' => $report->id,
            'reference' => $report->reference,
            'vehicle' => $report->vehicle ? [
                'licensePlate' => $report->vehicle->license_plate,
                'make' => $report->vehicle->make,
                'model' => $report->vehicle->model,
                'vin' => $report->vehicle->vin,
            ] : null,
            'createdBy' => $report->createdBy->full_name,
            'createdAt' => $report->created_at?->toIso8601String(),
            'faults' => $report->faults->map(fn ($fault) => [
                'id' => $fault->id,
                'severity' => $fault->severity,
                'errorCode' => $fault->error_code,
                'description' => $fault->description,
                'remark' => $fault->remark,
            ]),
        ];
    }
}
