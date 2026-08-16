<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Models\VehicleFault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlarmController extends Controller
{
    use ResolvesCompany;

    public function index(Request $request): Response
    {
        $company = $this->currentCompany($request);
        $vehicleIds = $company->vehicles()->pluck('id');

        $faults = VehicleFault::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->with('vehicle:id,license_plate,make,model')
            ->latest('log_time')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (VehicleFault $fault) => [
                'id' => $fault->id,
                'vehicle' => $fault->vehicle->license_plate ?? '—',
                'vehicleId' => $fault->vehicle_id,
                'code' => $fault->obd_code,
                'meaning' => $fault->meaning,
                'severity' => $fault->severity,
                'logTime' => $fault->log_time?->toIso8601String(),
                'clearedAt' => $fault->cleared_at?->toIso8601String(),
            ]);

        return Inertia::render('company/alarms/index', [
            'faults' => $faults,
        ]);
    }

    public function clear(Request $request): RedirectResponse
    {
        $company = $this->currentCompany($request);
        $vehicleIds = $company->vehicles()->pluck('id');

        $fault = VehicleFault::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->findOrFail((string) $request->route('fault'));

        $fault->cleared_at = now();
        $fault->save();

        return back();
    }
}
