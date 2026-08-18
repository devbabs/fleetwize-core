<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
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
class SyncVehicleTrips extends Command
{
    protected $signature = 'tracker:sync-trips';

    protected $description = "Pull completed trips from Traccar for every vehicle with a tracker attached, and sync them into vehicle_trips.";

    public function handle(TraccarService $traccar): int
    {
        $vehicles = Vehicle::query()->whereNotNull('obd_device_imei')->get();

        foreach ($vehicles as $vehicle) {
            try {
                $this->syncVehicle($vehicle, $traccar);
            } catch (Throwable $e) {
                Log::warning("Failed to sync trips for vehicle {$vehicle->id}.", ['error' => $e->getMessage()]);
                $this->error("Vehicle {$vehicle->id}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }

    protected function syncVehicle(Vehicle $vehicle, TraccarService $traccar): void
    {
        $device = $traccar->findDeviceByImei((string) $vehicle->obd_device_imei);

        if (! $device) {
            return;
        }

        $deviceId = (int) $device['id'];
        $trips = $traccar->tripsForDevice($deviceId, now()->subHours(6), now());

        foreach ($trips as $trip) {
            if (! isset($trip['startTime'], $trip['endTime'])) {
                continue;
            }

            $startTime = Carbon::parse($trip['startTime']);
            $endTime = Carbon::parse($trip['endTime']);

            VehicleTrip::query()->updateOrCreate(
                [
                    'vehicle_id' => $vehicle->id,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ],
                [
                    'obd_device_id' => (string) $deviceId,
                    'distance_km' => $trip['distance'] ?? null,
                    'average_speed_km_per_hr' => $trip['averageSpeed'] ?? null,
                    'max_speed_km_per_hr' => $trip['maxSpeed'] ?? null,
                    'fuel_consumed' => $trip['spentFuel'] ?? null,
                    'trip_date' => $startTime->toDateString(),
                    'start_odometer' => $this->sanitizeOdometer($trip['startOdometer'] ?? null),
                    'end_odometer' => $this->sanitizeOdometer($trip['endOdometer'] ?? null),
                    'start_latitude' => $trip['startLat'] ?? null,
                    'start_longitude' => $trip['startLon'] ?? null,
                    'end_latitude' => $trip['endLat'] ?? null,
                    'end_longitude' => $trip['endLon'] ?? null,
                    'start_address' => $trip['startAddress'] ?? null,
                    'end_address' => $trip['endAddress'] ?? null,
                    'driver_unique_id' => $trip['driverUniqueId'] ?? null,
                    'driver_name' => $trip['driverName'] ?? null,
                ],
            );
        }
    }

    /**
     * This device's odometer readings overflow to a ~2^32-1 sentinel
     * (confirmed via direct inspection of Traccar's Postgres data) instead
     * of reporting a real value — Traccar's trip report inherits the same
     * garbage since it derives startOdometer/endOdometer from the device's
     * own odometer attribute. Anything implausibly large is treated as
     * unavailable rather than stored.
     */
    protected function sanitizeOdometer(?float $value): ?float
    {
        return $value !== null && $value < 1_000_000 ? $value : null;
    }
}
