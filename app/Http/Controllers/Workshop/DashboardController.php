<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Workshop\Concerns\ResolvesWorkshop;
use App\Models\VehicleDiagnosticReport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use ResolvesWorkshop;

    public function index(Request $request): Response
    {
        $workshop = $this->currentWorkshop($request);

        $reportsThisMonth = $workshop->diagnosticReports()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $totalReports = $workshop->diagnosticReports()->count();

        $vehiclesServiced = $workshop->diagnosticReports()->distinct('vehicle_id')->count('vehicle_id');

        $recentReports = $workshop->diagnosticReports()
            ->with(['vehicle:id,license_plate,make,model', 'createdBy:id,first_name,last_name'])
            ->withCount('faults')
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (VehicleDiagnosticReport $report) => [
                'id' => $report->id,
                'reference' => $report->reference,
                'vehicle' => $report->vehicle->license_plate ?? '—',
                'faultsCount' => $report->faults_count,
                'createdBy' => $report->createdBy->full_name,
                'createdAt' => $report->created_at?->toIso8601String(),
            ]);

        return Inertia::render('workshop/dashboard', [
            'stats' => [
                'reportsThisMonth' => $reportsThisMonth,
                'totalReports' => $totalReports,
                'vehiclesServiced' => $vehiclesServiced,
            ],
            'recentReports' => $recentReports,
        ]);
    }
}
