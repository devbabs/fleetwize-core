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
        if ($vehicle->id === 13) {
            Log::info('Vehicle 13 sync started', [
                'vehicle_id' => $vehicle->id,
                'vehicle_name' => $vehicle->name,
                'imei' => $vehicle->obd_device_imei,
            ]);
        }

        $device = $traccar->findDeviceByImei((string) $vehicle->obd_device_imei);

        if ($vehicle->id === 13) {
            Log::info('Vehicle 13 device lookup result', [
                'device' => $device,
            ]);
        }

        if (! $device) {

            if ($vehicle->id === 13) {
                Log::warning('Vehicle 13 device not found in Traccar');
            }

            return;
        }

        $deviceId = (int) $device['id'];

        $from = now()->subHours(2);
        $to = now();

        if ($vehicle->id === 13) {
            Log::info('Vehicle 13 trip query window', [
                'device_id' => $deviceId,
                'from' => $from->toIso8601String(),
                'to' => $to->toIso8601String(),
            ]);
        }

        $trips = $traccar->tripsForDevice($deviceId, $from, $to);

        if ($vehicle->id === 13) {
            Log::info('Vehicle 13 trips returned', [
                'count' => count($trips),
            ]);

            Log::info('Vehicle 13 raw trip payload', [
                'trips' => $trips,
            ]);
        }

        foreach ($trips as $index => $trip) {

            if ($vehicle->id === 13) {
                Log::info('Vehicle 13 processing trip', [
                    'index' => $index,
                    'trip' => $trip,
                ]);
            }

            if (! isset($trip['startTime'], $trip['endTime'])) {

                if ($vehicle->id === 13) {
                    Log::warning('Vehicle 13 skipped trip - missing times', [
                        'trip' => $trip,
                    ]);
                }

                continue;
            }

            $startTime = Carbon::parse($trip['startTime']);
            $endTime = Carbon::parse($trip['endTime']);

            $data = [
                'obd_device_id' => (string) $deviceId,

                'distance_km' => $this->metersToKilometers($trip['distance'] ?? null),

                'average_speed_km_per_hr' => $this->knotsToKmPerHour($trip['averageSpeed'] ?? null),

                'max_speed_km_per_hr' => $this->knotsToKmPerHour($trip['maxSpeed'] ?? null),

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
            ];

            if ($vehicle->id === 13) {
                Log::info('Vehicle 13 transformed trip data', [
                    'start_time' => $startTime->toIso8601String(),
                    'end_time' => $endTime->toIso8601String(),
                    'data' => $data,
                ]);
            }

            $tripRecord = VehicleTrip::query()->updateOrCreate(
                [
                    'vehicle_id' => $vehicle->id,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ],
                $data
            );

            if ($vehicle->id === 13) {
                Log::info('Vehicle 13 trip saved', [
                    'trip_id' => $tripRecord->id,
                    'was_recently_created' => $tripRecord->wasRecentlyCreated,
                ]);
            }
        }

        if ($vehicle->id === 13) {
            Log::info('Vehicle 13 sync completed');
        }
    }

    protected function sanitizeOdometer(?float $value): ?float
    {
        return $value !== null && $value < 1_000_000 ? $value : null;
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
