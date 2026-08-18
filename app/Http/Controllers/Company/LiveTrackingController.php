<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LiveTrackingController extends Controller
{
    use ResolvesCompany;

    public function index(Request $request): Response
    {
        return Inertia::render('company/live-tracking/index', [
            'vehicles' => $this->positions($request)->getData(true)['vehicles'],
        ]);
    }

    /**
     * Polled by the map view every few seconds for fresh positions —
     * matches the mobile app's existing polling pattern (no websockets yet).
     */
    public function positions(Request $request): JsonResponse
    {
        $company = $this->currentCompany($request);

        $vehicles = $company->vehicles()
            ->with('trackerState')
            ->get()
            ->map(fn ($vehicle) => [
                'id' => $vehicle->id,
                'name' => $vehicle->name,
                'licensePlate' => $vehicle->license_plate,
                'category' => $vehicle->category,
                'isOnline' => $vehicle->trackerState?->isOnline() ?? false,
                'latitude' => $vehicle->trackerState?->latitude,
                'longitude' => $vehicle->trackerState?->longitude,
                'speed' => $vehicle->trackerState?->speed,
                'heading' => $vehicle->trackerState?->heading,
                'ignitionOn' => $vehicle->trackerState?->ignition_on,
                'fuelLevel' => $vehicle->trackerState?->fuel_level,
                'reportedAt' => $vehicle->trackerState?->reported_at?->toIso8601String(),
                'engineRpm' => $vehicle->trackerState?->engine_rpm,
                'engineLoad' => $vehicle->trackerState?->engine_load,
                'obdSpeed' => $vehicle->trackerState?->obd_speed,
                'isMoving' => $vehicle->trackerState?->is_moving,
                'batteryLevel' => $vehicle->trackerState?->battery_level,
                'satelliteCount' => $vehicle->trackerState?->satellite_count,
                'signalStrength' => $vehicle->trackerState?->signal_strength,
                'engineHours' => $vehicle->trackerState?->engine_hours,
                'isBlocked' => $vehicle->trackerState?->is_blocked,
                'isCharging' => $vehicle->trackerState?->is_charging,
            ])
            ->filter(fn (array $vehicle) => $vehicle['latitude'] !== null && $vehicle['longitude'] !== null)
            ->values();

        return response()->json(['vehicles' => $vehicles]);
    }
}
