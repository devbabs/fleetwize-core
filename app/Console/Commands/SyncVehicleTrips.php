<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use App\Models\VehicleAssignment;
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


    protected function syncVehicle(Vehicle $vehicle, TraccarService $traccar): void 
    {
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
            'device_id' => $vehicle->traccar_device_id,
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
        ]);

        $trips = $traccar->tripsForDevice(
            $vehicle->traccar_device_id,
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

            $companyUserId = $this->driverCompanyUserId(
                $vehicle,
                $startTime
            );

            $startAddress = $this->resolveTripAddress(
                $traccar,
                $trip['startAddress'] ?? null,
                isset($trip['startLat']) ? (float) $trip['startLat'] : null,
                isset($trip['startLon']) ? (float) $trip['startLon'] : null
            );

            $endAddress = $this->resolveTripAddress(
                $traccar,
                $trip['endAddress'] ?? null,
                isset($trip['endLat']) ? (float) $trip['endLat'] : null,
                isset($trip['endLon']) ? (float) $trip['endLon'] : null
            );

                // VehicleTrip::query()->updateOrCreate(
                //     [
                //         'vehicle_id' => $vehicle->id,
                //         'start_time' => $startTime,
                //         'end_time'   => $endTime,
                //     ],
                //     [
                //         'company_user_id' => $companyUserId,
                        
                //         'obd_device_id' =>
                //             (string) $vehicle->traccar_device_id,

                //         'distance_km' =>
                //             $this->metersToKilometers(
                //                 $trip['distance'] ?? null
                //             ),

                //         'average_speed_km_per_hr' =>
                //             $this->knotsToKmPerHour(
                //                 $trip['averageSpeed'] ?? null
                //             ),

                //         'max_speed_km_per_hr' =>
                //             $this->knotsToKmPerHour(
                //                 $trip['maxSpeed'] ?? null
                //             ),

                //         'fuel_consumed' =>
                //             $trip['spentFuel'] ?? null,

                //         'trip_date' =>
                //             $startTime->toDateString(),

                //         'start_odometer' =>
                //             $this->metersToKilometers(
                //                 $trip['startOdometer'] ?? null
                //             ),

                //         'end_odometer' =>
                //             $this->metersToKilometers(
                //                 $trip['endOdometer'] ?? null
                //             ),

                //         'start_position_id' =>
                //             $trip['startPositionId'] ?? null,

                //         'end_position_id' =>
                //             $trip['endPositionId'] ?? null,

                //         'duration_seconds' => isset($trip['duration'])
                //                 ? (int) ($trip['duration'] / 1000)
                //                 : null,

                //         'start_latitude' =>
                //             $trip['startLat'] ?? null,

                //         'start_longitude' =>
                //             $trip['startLon'] ?? null,

                //         'end_latitude' =>
                //             $trip['endLat'] ?? null,

                //         'end_longitude' =>
                //             $trip['endLon'] ?? null,

                //         'start_address' => $startAddress,

                //         'end_address'   => $endAddress,

                //         'driver_unique_id' =>
                //             $trip['driverUniqueId'] ?? null,

                //         'driver_name' =>
                //             $trip['driverName'] ?? null,
                //     ]
                // );

                VehicleTrip::query()->updateOrCreate(
                    [
                        'vehicle_id' => $vehicle->id,
                        'start_time' => $startTime,
                    ],
                    [
                        'end_time'                => $endTime,
                        'company_user_id'         => $companyUserId,
                        'obd_device_id'           => (string) $vehicle->traccar_device_id,
                        'distance_km'             => $this->metersToKilometers($distanceMeters),
                        'average_speed_km_per_hr' => $avgSpeed,
                        'max_speed_km_per_hr'     => $maxSpeed,
                        'fuel_consumed'           => $trip['spentFuel'] ?? null,
                        'trip_date'               => $tripDate,
                        'start_odometer'          => $this->metersToKilometers($trip['startOdometer'] ?? null),
                        'end_odometer'            => $this->metersToKilometers($trip['endOdometer'] ?? null),
                        'start_position_id'       => $trip['startPositionId'] ?? null,
                        'end_position_id'         => $trip['endPositionId'] ?? null,
                        'duration_seconds'        => (int) ($durationMs / 1000),
                        'start_latitude'          => $trip['startLat'] ?? null,
                        'start_longitude'         => $trip['startLon'] ?? null,
                        'end_latitude'            => $trip['endLat'] ?? null,
                        'end_longitude'           => $trip['endLon'] ?? null,
                        'start_address'           => $startAddress,
                        'end_address'             => $endAddress,
                        'driver_unique_id'        => $trip['driverUniqueId'] ?? null,
                        'driver_name'             => $trip['driverName'] ?? null,
                    ]
                );

            $saved++;
        }

        $vehicle->update([
            'last_trip_sync_at' => $to,
            'route_synced_at' => now(),
        ]);

        $this->info(
            "Vehicle {$vehicle->id}: {$saved} trip(s) synced."
        );
    }

    protected function resolveTripAddress(TraccarService $traccar, ?string $address, ?float $latitude, ?float $longitude): ?string 
    {
        if (!empty($address)) {
            return $address;
        }

        if ($latitude === null || $longitude === null) {
            return null;
        }

        try {
            return $traccar->reverseGeocode(
                $latitude,
                $longitude
            );
        } catch (Throwable $e) {
            Log::warning('Failed to geocode trip address.', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'error' => $e->getMessage(),
            ]);

            return null;
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


    protected function driverCompanyUserId(Vehicle $vehicle, Carbon $tripStart): ?int 
    {
        $assignment = VehicleAssignment::query()
            ->where('vehicle_id', $vehicle->id)
            // ->where('start_date', '<=', $tripStart->toDateString())
            // ->where(function ($query) use ($tripStart) {
            //     $query->whereNull('end_date')
            //         ->orWhere(
            //             'end_date',
            //             '>=',
            //             $tripStart->toDateString()
            //         );
            // })
            ->whereHas('companyUser', function ($query) {
                $query->where('role', 'driver');
            })
            ->latest('start_date')
            ->first();

        return $assignment?->company_user_id;
    }
}
