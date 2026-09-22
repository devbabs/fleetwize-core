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

    protected $description = 'Pull completed trips from Traccar and sync them into vehicle_trips.';

    public function handle(TraccarService $traccar): int
    {
        $vehicles = Vehicle::query()
            ->whereNotNull('obd_device_imei')
            ->get();

        foreach ($vehicles as $vehicle) {
            try {
                $this->syncVehicle($vehicle, $traccar);
            } catch (Throwable $e) {
                Log::warning(
                    "Failed to sync trips for vehicle {$vehicle->id}.",
                    [
                        'error' => $e->getMessage(),
                    ]
                );

                $this->error(
                    "Vehicle {$vehicle->id}: {$e->getMessage()}"
                );
            }
        }

        return self::SUCCESS;
    }

    protected function syncVehicle(
        Vehicle $vehicle,
        TraccarService $traccar
    ): void {
        $device = $traccar->findDeviceByImei(
            (string) $vehicle->obd_device_imei
        );

        if (! $device) {
            $this->warn(
                "Traccar device not found for Vehicle {$vehicle->id}."
            );

            return;
        }

        $deviceId = (int) $device['id'];

        $lastSyncAt = $vehicle->last_trip_sync_at;

        $from = $lastSyncAt
            ? Carbon::parse($lastSyncAt)->subMinutes(5)
            : now()->subDay();

        $to = now();

        $this->line(
            "Vehicle {$vehicle->id}: " .
            "{$from->toIso8601String()} → {$to->toIso8601String()}"
        );

        Log::info('Trip sync window', [
            'vehicle_id' => $vehicle->id,
            'device_id' => $deviceId,
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
        ]);

        $trips = $traccar->tripsForDevice(
            $deviceId,
            $from,
            $to
        );

        $saved = 0;

        foreach ($trips as $trip) {
            if (
                ! isset(
                    $trip['startTime'],
                    $trip['endTime']
                )
            ) {
                continue;
            }

            $startTime = Carbon::parse($trip['startTime']);
            $endTime = Carbon::parse($trip['endTime']);

                VehicleTrip::query()->updateOrCreate(
                    [
                        'vehicle_id' => $vehicle->id,
                        'start_time' => $startTime,
                        'end_time'   => $endTime,
                    ],
                    [
                        'obd_device_id' =>
                            (string) $deviceId,

                        'distance_km' =>
                            $this->metersToKilometers(
                                $trip['distance'] ?? null
                            ),

                        'average_speed_km_per_hr' =>
                            $this->knotsToKmPerHour(
                                $trip['averageSpeed'] ?? null
                            ),

                        'max_speed_km_per_hr' =>
                            $this->knotsToKmPerHour(
                                $trip['maxSpeed'] ?? null
                            ),

                        'fuel_consumed' =>
                            $trip['spentFuel'] ?? null,

                        'trip_date' =>
                            $startTime->toDateString(),

                        'start_odometer' =>
                            $this->metersToKilometers(
                                $trip['startOdometer'] ?? null
                            ),

                        'end_odometer' =>
                            $this->metersToKilometers(
                                $trip['endOdometer'] ?? null
                            ),

                        'start_position_id' =>
                            $trip['startPositionId'] ?? null,

                        'end_position_id' =>
                            $trip['endPositionId'] ?? null,

                        'duration_seconds' => isset($trip['duration'])
                                ? (int) ($trip['duration'] / 1000)
                                : null,

                        'start_latitude' =>
                            $trip['startLat'] ?? null,

                        'start_longitude' =>
                            $trip['startLon'] ?? null,

                        'end_latitude' =>
                            $trip['endLat'] ?? null,

                        'end_longitude' =>
                            $trip['endLon'] ?? null,

                        'start_address' =>
                            $trip['startAddress'] ?? null,

                        'end_address' =>
                            $trip['endAddress'] ?? null,

                        'driver_unique_id' =>
                            $trip['driverUniqueId'] ?? null,

                        'driver_name' =>
                            $trip['driverName'] ?? null,
                    ]
                );

            $saved++;
        }

        /*
         * Only move the watermark after the Traccar
         * request and database processing completed.
         */
        $vehicle->update([
            'last_trip_sync_at' => $to,
        ]);

        $this->info(
            "Vehicle {$vehicle->id}: {$saved} trip(s) synced."
        );
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
