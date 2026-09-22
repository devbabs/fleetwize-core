<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use App\Models\VehicleTrip;
use App\Services\Tracking\TraccarService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Traccar computes trip aggregates server-side (/api/reports/trips) — a
 * trip is only known once it's over, so unlike positions/events this has
 * to be pulled on an interval rather than pushed. Scheduled via
 * bootstrap/app.php's withSchedule().
 */
class BackFillTrips extends Command
{
    protected $signature = 'tracker:backfill-trips 
                            {--fresh : Truncate the vehicle_trips table before starting}
                            {--from=2026-09-01 : Start date for backfill}
                            {--vehicle= : Backfill for a single vehicle ID}';

    protected $description = 'Wipe and backfill vehicle trips day-by-day from Traccar up until today.';

    public function handle(TraccarService $traccar): int
    {
        // 1. Wipe existing trips if --fresh is provided
        if ($this->option('fresh')) {
            $this->warn('Truncating vehicle_trips table...');
            Schema::disableForeignKeyConstraints();
            DB::table('vehicle_trips')->truncate();
            Schema::enableForeignKeyConstraints();
            $this->info('vehicle_trips table cleared.');
        }

        $startDate = Carbon::parse($this->option('from'))->startOfDay();
        $today = now();

        $query = Vehicle::query()->whereNotNull('obd_device_imei');

        if ($vehicleId = $this->option('vehicle')) {
            $query->where('id', $vehicleId);
        }

        $vehicles = $query->get();

        $this->info("Starting backfill from {$startDate->toDateString()} to {$today->toDateString()}");
        $this->info("Vehicles to process: {$vehicles->count()}");

        foreach ($vehicles as $vehicle) {
            $this->line("--------------------------------------------------");
            $this->info("Processing Vehicle #{$vehicle->id}: {$vehicle->name} (IMEI: {$vehicle->obd_device_imei})");

            try {
                $device = $traccar->findDeviceByImei((string) $vehicle->obd_device_imei);

                if (! $device) {
                    $this->warn("Device not found in Traccar for Vehicle #{$vehicle->id}. Skipping.");
                    continue;
                }

                $deviceId = (int) $device['id'];
                $totalVehicleTrips = 0;

                // 2. Iterate Day by Day
                $currentDay = $startDate->copy();

                while ($currentDay->lte($today)) {
                    $dayStart = $currentDay->copy()->startOfDay();
                    // If it's today, only query up to the current minute
                    $dayEnd = $currentDay->isSameDay($today) ? $today->copy() : $currentDay->copy()->endOfDay();

                    $this->line("  -> Fetching {$dayStart->toDateString()} ({$dayStart->format('H:i')} - {$dayEnd->format('H:i')})...");

                    $trips = $traccar->tripsForDevice($deviceId, $dayStart, $dayEnd);
                    $count = count($trips);
                    $totalVehicleTrips += $count;

                    if ($count > 0) {
                        $this->saveTrips($vehicle->id, $deviceId, $trips);
                        $this->line("     Saved {$count} trip(s).");
                    }

                    // Move to the next day
                    $currentDay->addDay();

                    // Optional 100ms throttle to prevent Traccar request flood
                    usleep(100000);
                }

                $this->info("Finished Vehicle #{$vehicle->id}. Total synced: {$totalVehicleTrips} trips.");

                $vehicle->update([
                    'last_trip_sync_at' => $today,
                ]);

            } catch (Throwable $e) {
                Log::warning("Failed backfilling for vehicle {$vehicle->id}.", ['error' => $e->getMessage()]);
                $this->error("Vehicle {$vehicle->id} failed: {$e->getMessage()}");
            }
        }

        $this->info('====================================');
        $this->info('Trip backfill completed successfully.');

        return self::SUCCESS;
    }

    protected function saveTrips(int $vehicleId, int $deviceId, array $trips): void
    {
        foreach ($trips as $trip) {
            if (! isset($trip['startTime'], $trip['endTime'])) {
                continue;
            }

            $startTime = Carbon::parse($trip['startTime']);
            $endTime = Carbon::parse($trip['endTime']);

            VehicleTrip::query()->updateOrCreate(
                [
                    'vehicle_id' => $vehicleId,
                    'start_time' => $startTime,
                    'end_time'   => $endTime,
                ],
                [
                    'obd_device_id'           => (string) $deviceId,

                    // Traccar distance is meters
                    'distance_km'              => $this->metersToKilometers(
                        $trip['distance'] ?? null
                    ),

                    // Traccar speeds are knots
                    'average_speed_km_per_hr'  => $this->knotsToKmPerHour(
                        $trip['averageSpeed'] ?? null
                    ),

                    'max_speed_km_per_hr'      => $this->knotsToKmPerHour(
                        $trip['maxSpeed'] ?? null
                    ),

                    'fuel_consumed'            => $trip['spentFuel'] ?? null,

                    'trip_date'                => $startTime->toDateString(),

                    // Traccar odometer is meters
                    'start_odometer'           => $this->metersToKilometers(
                        $trip['startOdometer'] ?? null
                    ),

                    'end_odometer'             => $this->metersToKilometers(
                        $trip['endOdometer'] ?? null
                    ),

                    'start_position_id'        => $trip['startPositionId'] ?? null,

                    'end_position_id'          => $trip['endPositionId'] ?? null,

                    'duration_seconds'         => isset($trip['duration'])
                        ? (int) ($trip['duration'] / 1000)
                        : null,

                    'start_latitude'           => $trip['startLat'] ?? null,
                    'start_longitude'          => $trip['startLon'] ?? null,
                    'end_latitude'             => $trip['endLat'] ?? null,
                    'end_longitude'            => $trip['endLon'] ?? null,
                    'start_address'            => $trip['startAddress'] ?? null,
                    'end_address'              => $trip['endAddress'] ?? null,
                    'driver_unique_id'         => $trip['driverUniqueId'] ?? null,
                    'driver_name'              => $trip['driverName'] ?? null,
                ]
            );

        }
    }

    // protected function odometerMetersToKilometers(?float $meters): ?float
    // {
    //     return $meters !== null
    //         ? round($meters / 1000, 2)
    //         : null;
    // }

    protected function metersToKilometers(?float $meters): ?float
    {
        return $meters !== null ? round($meters / 1000, 2) : null;
    }

    protected function knotsToKmPerHour(?float $knots): ?float
    {
        return $knots !== null ? round($knots * 1.852, 2) : null;
    }
}
