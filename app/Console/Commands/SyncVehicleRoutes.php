<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use App\Models\VehicleRoutePoint;
use App\Models\VehicleTrip;
use App\Services\Tracking\TraccarService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Traccar computes trip aggregates server-side (/api/reports/trips) — a
 * trip is only known once it's over, so unlike positions/events this has
 * to be pulled on an interval rather than pushed. Scheduled via
 * bootstrap/app.php's withSchedule().
 */
class SyncVehicleRoutes extends Command
{
    protected $signature = 'tracker:sync-routes';

    protected $description = 'Pull route data from Traccar and sync it into vehicle_route_points.';

    public function handle(TraccarService $traccar): int 
    {
        Vehicle::query()
            ->whereNotNull('traccar_device_id')
            ->chunk(100, function ($vehicles) use ($traccar) {

                foreach ($vehicles as $vehicle) {

                    $this->syncVehicle(
                        $vehicle,
                        $traccar
                    );
                }
            });

        return self::SUCCESS;
    }

    protected function syncVehicle(Vehicle $vehicle, TraccarService $traccar): void 
    {
        $trips = VehicleTrip::query()
            ->where('vehicle_id', $vehicle->id)
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->whereNull('route_synced_at')
            ->get();

        foreach ($trips as $trip) {

            $this->syncTrip(
                $vehicle,
                $trip,
                $traccar
            );
        }
    }

    protected function syncTrip(Vehicle $vehicle, VehicleTrip $trip, TraccarService $traccar): void 
    {

        $positions = $traccar->routeReport(
            $vehicle->traccar_device_id,
            $trip->start_time,
            $trip->end_time
        );

        foreach ($positions as $position) {

            VehicleRoutePoint::updateOrCreate(
                [
                    'traccar_position_id' => $position['id'],
                ],
                [
                    'vehicle_id' => $vehicle->id,
                    'traccar_device_id' => $vehicle->traccar_device_id,
                    'vehicle_trip_id' => $trip->id,

                    'latitude' => $position['latitude'],
                    'longitude' => $position['longitude'],

                    'speed_kmh' => round(
                        ($position['speed'] ?? 0) * 1.852,
                        2
                    ),

                    'course' => $position['course'] ?? null,

                    'ignition' => data_get(
                        $position,
                        'attributes.ignition'
                    ),

                    'motion' => data_get(
                        $position,
                        'attributes.motion'
                    ),

                    'odometer' => data_get(
                        $position,
                        'attributes.odometer'
                    ),

                    'fix_time' => $position['fixTime'],

                    'device_time' => $position['deviceTime'],

                    'server_time' => $position['serverTime'],

                    'raw_payload' => $position,
                ]
            );
        }
    }

    protected function metersToKilometers(?float $meters): ?float
    {
        return $meters !== null
            ? round($meters / 1000, 2)
            : null;
    }

    protected function knotsToKmPerHour(?float $knots): ?float
    {
        return $knots !== null
            ? round($knots * 1.852, 2)
            : null;
    }
}
