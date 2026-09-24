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
            // ->chunk(100, function ($vehicles) use ($traccar) {
            ->chunkById(100, function ($vehicles) use ($traccar) {

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
            Carbon::parse($trip->start_time),
            Carbon::parse($trip->end_time)
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

                    'latitude' => $position['latitude'] ?? null,
                    'longitude' => $position['longitude'] ?? null,

                    'altitude' => $position['altitude'] ?? null,
                    'accuracy' => $position['accuracy'] ?? null,
                    'valid' => $position['valid'] ?? null,

                    'course' => $position['course'] ?? null,

                    'speed_kmh' => $this->knotsToKmPerHour($position['speed'] ?? null),

                    'obd_speed_kmh' => $this->knotsToKmPerHour(data_get($position, 'attributes.obdSpeed')),

                    'ignition' => data_get($position,'attributes.ignition'),

                    'motion' => data_get($position,'attributes.motion'),

                    'blocked' => data_get($position,'attributes.blocked'),

                    'charge' => data_get($position,'attributes.charge'),

                    'odometer' => $this->metersToKilometers(data_get($position, 'attributes.odometer')),

                    'obd_odometer' => $this->metersToKilometers(data_get($position, 'attributes.obdOdometer')),

                    'distance' => $this->metersToKilometers(data_get($position, 'attributes.distance')),

                    'total_distance' => $this->metersToKilometers(data_get($position, 'attributes.totalDistance')),

                    'fuel' => data_get($position,'attributes.fuel'),

                    'fuel_consumption' => data_get($position,'attributes.fuelConsumption'),

                    'rpm' => data_get($position,'attributes.rpm'),

                    'engine_load' => data_get($position,'attributes.engineLoad'),

                    'power' => data_get($position,'attributes.power'),

                    'battery' => data_get($position,'attributes.battery'),

                    'battery_level' => data_get($position,'attributes.batteryLevel'),

                    'sat' => data_get($position,'attributes.sat'),

                    'rssi' => data_get($position,'attributes.rssi'),

                    'hours' => data_get($position,'attributes.hours'),

                    'hard_acceleration_count' => data_get($position,'attributes.hardAccelerationCount'),

                    'hard_deceleration_count' => data_get($position,'attributes.hardDecelerationCount'),

                    'hard_cornering_count' => data_get($position,'attributes.hardCorneringCount'),

                    'fix_time' => $position['fixTime'] ?? null,
                    'device_time' => $position['deviceTime'] ?? null,
                    'server_time' => $position['serverTime'] ?? null,

                    'raw_payload' => $position,
                ]
            );
        }

        $trip->update([
            'route_synced_at' => now(),
        ]);
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
