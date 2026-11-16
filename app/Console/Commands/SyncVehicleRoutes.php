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
            $obdSpeedRaw = data_get($position, 'attributes.obdSpeed')
                ?? data_get($position, 'attributes.io81');

            $gpsSpeedKmh = isset($position['speed'])
                ? $this->knotsToKmPerHour((float) $position['speed'])
                : null;

            // Guard against GPS drift spikes when fix is invalid or satellites < 4
            if (data_get($position, 'valid') === false || (int) data_get($position, 'attributes.sat', 0) < 4) {
                $gpsSpeedKmh = 0.0;
            }

            $rawPrimaryOdometer = data_get($position, 'attributes.io87')
                ?? data_get($position, 'attributes.odometer');

            $rawObdOdometer = data_get($position, 'attributes.obdOdometer')
                ?? data_get($position, 'attributes.io87');

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

                    'speed_kmh' => $gpsSpeedKmh,
                    // OBD / CAN wheel speed (already in km/h, do not multiply by 1.852)
                    'obd_speed_kmh' => $obdSpeedRaw !== null ? round((float) $obdSpeedRaw, 1) : null,

                    'ignition' => data_get($position, 'attributes.ignition')
                        ?? (data_get($position, 'attributes.io1') !== null ? (bool) data_get($position, 'attributes.io1') : null),
                    'motion' => data_get($position, 'attributes.motion'),
                    'blocked' => data_get($position, 'attributes.blocked'),
                    'charge' => data_get($position, 'attributes.charge'),

                    'odometer' => $this->metersToKilometers($rawPrimaryOdometer !== null ? (float) $rawPrimaryOdometer : null),
                    'obd_odometer' => $this->metersToKilometers($rawObdOdometer !== null ? (float) $rawObdOdometer : null),
                    'distance' => $this->metersToKilometers(data_get($position, 'attributes.distance')),
                    'total_distance' => $this->metersToKilometers(data_get($position, 'attributes.totalDistance')),

                    // JT808 'fuel' or Teltonika CAN 'io84' (0.1 L multiplier)
                    'fuel' => data_get($position, 'attributes.fuel')
                        ?? (data_get($position, 'attributes.io84') !== null ? round(((float) data_get($position, 'attributes.io84')) * 0.1, 1) : null),

                    // JT808 'fuelConsumption' or Teltonika CAN 'io107' (0.1 L multiplier)
                    'fuel_consumption' => data_get($position, 'attributes.fuelConsumption')
                        ?? (data_get($position, 'attributes.io107') !== null ? round(((float) data_get($position, 'attributes.io107')) * 0.1, 1) : null),

                    // JT808 'rpm' or Teltonika CAN 'io85'
                    'rpm' => data_get($position, 'attributes.rpm')
                        ?? data_get($position, 'attributes.io85'),

                    // JT808 'engineLoad' or Teltonika CAN 'io82' (throttle position %)
                    'engine_load' => data_get($position, 'attributes.engineLoad')
                        ?? data_get($position, 'attributes.io82'),

                    'power' => data_get($position, 'attributes.power'),
                    'battery' => data_get($position, 'attributes.battery'),
                    'battery_level' => data_get($position, 'attributes.batteryLevel'),
                    'sat' => data_get($position, 'attributes.sat'),
                    'rssi' => data_get($position, 'attributes.rssi'),
                    'hours' => data_get($position, 'attributes.hours')
                        ?? (data_get($position, 'attributes.io103') !== null ? ((int) data_get($position, 'attributes.io103')) * 60000 : null),

                    'hard_acceleration_count' => data_get($position, 'attributes.hardAccelerationCount'),
                    'hard_deceleration_count' => data_get($position, 'attributes.hardDecelerationCount'),
                    'hard_cornering_count' => data_get($position, 'attributes.hardCorneringCount'),

                    'fix_time' => $position['fixTime'] ?? null,
                    'device_time' => $position['deviceTime'] ?? null,
                    'server_time' => $position['serverTime'] ?? null,

                    'raw_payload' => $position,

                    'fuel_level_percent' => data_get($position, 'attributes.io89'),
                    'coolant_temp' => data_get($position, 'attributes.coolantTemp')
                        ?? (data_get($position, 'attributes.io115') !== null ? round(((float) data_get($position, 'attributes.io115')) * 0.1, 1) : null),
                    'green_driving_event' => data_get($position, 'attributes.io253'),
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
