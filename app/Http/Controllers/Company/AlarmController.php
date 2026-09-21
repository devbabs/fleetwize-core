<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Models\VehicleAlarm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\SystemLogService;

class AlarmController extends Controller
{
    use ResolvesCompany;

    public function index(Request $request): Response
    {
        $company = $this->currentCompany($request);

        $vehicles = $company->vehicles()
                ->select('id', 'license_plate')
                ->orderBy('license_plate')
                ->get();

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
            'vehicles' => $vehicles,
            'filters' => [
                'vehicle_id' => $request->vehicle_id,
            ],
        ]);
    }

    public function clear(Request $request, SystemLogService $systemLog): RedirectResponse
    {
        $company = $this->currentCompany($request);
        $vehicleIds = $company->vehicles()->pluck('id');

        $alarm = VehicleAlarm::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->findOrFail((string) $request->route('fault'));

        $alarm->acknowledged_at = now();
        $alarm->save();

         $systemLog->log(
            event: 'alarm.acknowledged',
            description: "Acknowledged alarm {$alarm->alarm_type} for vehicle ID {$alarm->vehicle_id}.",
            subject: $alarm,
            company: $company,
            metadata: [
                'alarm_id' => $alarm->id,
                'alarm_type' => $alarm->alarm_type,
                'vehicle_id' => $alarm->vehicle_id,
                'gps_time' => $alarm->gps_time?->toIso8601String(),
            ],
        );

        return back();
    }
}
