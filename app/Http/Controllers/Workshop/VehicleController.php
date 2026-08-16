<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Workshop\Concerns\ResolvesWorkshop;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    use ResolvesWorkshop;

    public function index(Request $request): Response
    {
        $company = $this->currentCompany($request);

        $vehicles = $company->vehicles()
            ->withCount(['faults' => fn ($query) => $query->whereNull('cleared_at')])
            ->orderBy('license_plate')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Vehicle $vehicle) => [
                'id' => $vehicle->id,
                'licensePlate' => $vehicle->license_plate,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'vin' => $vehicle->vin,
                'openFaultsCount' => $vehicle->faults_count,
            ]);

        return Inertia::render('workshop/vehicles/index', [
            'vehicles' => $vehicles,
        ]);
    }

    public function show(Request $request): Response
    {
        $company = $this->currentCompany($request);

        $vehicle = $company->vehicles()
            ->with(['faults' => fn ($query) => $query->latest('log_time')->limit(30)])
            ->findOrFail((string) $request->route('vehicle'));

        return Inertia::render('workshop/vehicles/show', [
            'vehicle' => [
                'id' => $vehicle->id,
                'licensePlate' => $vehicle->license_plate,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'year' => $vehicle->year,
                'vin' => $vehicle->vin,
            ],
            'faults' => $vehicle->faults->map(fn ($fault) => [
                'id' => $fault->id,
                'code' => $fault->obd_code,
                'meaning' => $fault->meaning,
                'severity' => $fault->severity,
                'logTime' => $fault->log_time?->toIso8601String(),
                'clearedAt' => $fault->cleared_at?->toIso8601String(),
            ]),
        ]);
    }
}
