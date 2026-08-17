<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Models\VehicleAlarm;
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

        $faults = VehicleAlarm::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->with('vehicle:id,license_plate,make,model')
            ->latest('gps_time')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (VehicleAlarm $alarm) => [
                'id' => $alarm->id,
                'vehicle' => $alarm->vehicle->license_plate ?? '—',
                'vehicleId' => $alarm->vehicle_id,
                'code' => $alarm->alarm_type,
                'meaning' => $alarm->alarm_description ?? $alarm->description,
                'severity' => $alarm->severity(),
                'logTime' => $alarm->gps_time?->toIso8601String(),
                'clearedAt' => $alarm->acknowledged_at?->toIso8601String(),
            ]);

        return Inertia::render('company/alarms/index', [
            'faults' => $faults,
        ]);
    }

    public function clear(Request $request): RedirectResponse
    {
        $company = $this->currentCompany($request);
        $vehicleIds = $company->vehicles()->pluck('id');

        $alarm = VehicleAlarm::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->findOrFail((string) $request->route('fault'));

        $alarm->acknowledged_at = now();
        $alarm->save();

        return back();
    }
}
