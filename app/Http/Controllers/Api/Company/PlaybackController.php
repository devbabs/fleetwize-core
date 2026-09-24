<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Models\VehicleRoutePoint;
use App\Models\VehicleTrip;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlaybackController extends Controller
{
    use ResolvesCompany;
    //
    public function show(Request $request): Response
    {
        $company = $this->currentCompany($request);

        $vehicle = $company->vehicles()
            ->findOrFail(
                $request->route('vehicle')
            );

        $trip = VehicleTrip::query()
            ->where('vehicle_id', $vehicle->id)
            ->findOrFail(
                $request->route('trip')
            );

        $points = VehicleRoutePoint::query()
            ->where('vehicle_trip_id', $trip->id)
            ->orderBy('fix_time')
            ->orderBy('traccar_position_id')
            ->get();

        return Inertia::render(
            'company/vehicles/trips/playback',
            [
                'vehicle' => [
                    'id' => $vehicle->id,
                    'name' => $vehicle->name,
                ],

                'trip' => [
                    'id' => $trip->id,

                    'startTime' => $trip->start_time?->toIso8601String(),
                    'endTime' => $trip->end_time?->toIso8601String(),

                    'distanceKm' => $trip->distance_km,
                    'durationSeconds' => $trip->duration_seconds,
                    'averageSpeed' => $trip->average_speed_km_per_hr,
                    'maxSpeed' => $trip->max_speed_km_per_hr,

                    'startAddress' => $trip->start_address,
                    'endAddress' => $trip->end_address,

                    'driverName' => $trip->driver_name,
                ],

                'routePoints' => $points->map(fn ($point) => [
                    'id' => $point->id,

                    'lat' => $point->latitude,
                    'lng' => $point->longitude,

                    'speed' => $point->speed_kmh,
                    'heading' => $point->course,

                    'fixTime' => $point->fix_time?->toIso8601String(),

                    'ignition' => $point->ignition,
                    'motion' => $point->motion,

                    'altitude' => $point->altitude,
                    'battery' => $point->battery,
                    'rssi' => $point->rssi,
                    'sat' => $point->sat,
                ]),
            ]
        );
    }
}
