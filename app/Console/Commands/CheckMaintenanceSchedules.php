<?php

namespace App\Console\Commands;

use App\Models\MaintenanceAlert;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceSchedule;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

// class CheckMaintenanceSchedules extends Command
// {

//     protected $signature = 'maintenance:check';

//     protected $description = 'Check vehicles for due maintenance schedules';
//     /**
//      * Execute the console command.
//      */
//     public function handle()
//     {
//         //
//         MaintenanceSchedule::query()
//             ->where('active', true)
//             ->with('vehicle')
//             ->chunk(100, function ($schedules) {

//                 foreach ($schedules as $schedule) {
//                     $this->checkSchedule($schedule);
//                 }
//             });

//         return self::SUCCESS;
//     }

//     protected function checkSchedule(MaintenanceSchedule $schedule): void
//     {
//         $vehicle = $schedule->vehicle;

//         $lastMaintenance = $vehicle
//             ->maintenanceRecords()
//             ->latest('maintained_at')
//             ->first();

//         if (! $lastMaintenance) {
//             return;
//         }

//         $currentOdometer =
//             $vehicle->trackerState?->odometer;

//         if (! $currentOdometer) {
//             return;
//         }

//         $distanceTravelled =
//             $currentOdometer -
//             $lastMaintenance->odometer_km;

//         $daysElapsed =
//             $lastMaintenance->maintained_at
//                 ->diffInDays(now());

//         $distanceExceeded =
//             $schedule->distance_interval_km &&
//             $distanceTravelled >= $schedule->distance_interval_km;

//         $timeExceeded =
//             $schedule->time_interval_days &&
//             $daysElapsed >= $schedule->time_interval_days;

//         if ($distanceExceeded || $timeExceeded) {
//             $this->createAlert(
//                 $schedule,
//                 $distanceTravelled,
//                 $daysElapsed
//             );
//         }
//     }

//     protected function createAlert(MaintenanceSchedule $schedule, float $distanceTravelled, int $daysElapsed): void
//     {
//         $exists = MaintenanceAlert::query()
//             ->where('vehicle_id', $schedule->vehicle_id)
//             ->where('maintenance_schedule_id', $schedule->id)
//             ->where('acknowledged', false)
//             ->exists();

//         if ($exists) {
//             return;
//         }

//         MaintenanceAlert::create([
//             'vehicle_id' => $schedule->vehicle_id,
//             'maintenance_schedule_id' => $schedule->id,
//             'title' => "{$schedule->name} Due",
//             'description' =>
//                 "Distance travelled: {$distanceTravelled} km. Days since last service: {$daysElapsed}.",
//             'alerted_at' => now(),
//         ]);
//     }
// }
// class CheckMaintenanceSchedules extends Command
// {
//     protected $signature = 'maintenance:check';

//     protected $description = 'Check vehicles for due maintenance schedules';

//     public function handle(): int
//     {
//         $schedules = MaintenanceSchedule::query()
//             ->where('active', true)
//             ->with([
//                 'vehicle.trackerState',
//             ])
//             ->get();

//         foreach ($schedules as $schedule) {
//             $vehicle = $schedule->vehicle;

//             if (! $vehicle) {
//                 continue;
//             }

//             $latestRecord = MaintenanceRecord::query()
//                 ->where('vehicle_id', $vehicle->id)
//                 ->where('maintenance_schedule_id', $schedule->id)
//                 ->latest('maintained_at')
//                 ->first();

//             $baselineOdometer =
//                 $latestRecord?->odometer_km
//                 ?? $schedule->baseline_odometer_km;

//             $baselineDate =
//                 $latestRecord?->maintained_at
//                 ?? $schedule->baseline_started_at;

//             // $currentOdometer = $vehicle->trackerState?->odometer;

//             $currentOdometer = $vehicle->trackerState?->odometer ?? $vehicle->mileage;

//             $distanceDue = false;
//             $timeDue = false;

//             /*
//              * Distance check
//              */
//             if (
//                 $schedule->distance_interval_km !== null &&
//                 $currentOdometer !== null &&
//                 $baselineOdometer !== null
//             ) {
//                 $distanceTravelled =
//                     $currentOdometer - $baselineOdometer;

//                 $distanceDue =
//                     $distanceTravelled >=
//                     $schedule->distance_interval_km;
//             }

//             /*
//              * Time check
//              */
//             if (
//                 $schedule->time_interval_days !== null &&
//                 $baselineDate !== null
//             ) {
//                 $daysElapsed =
//                     $baselineDate->diffInDays(now());

//                 $timeDue =
//                     $daysElapsed >=
//                     $schedule->time_interval_days;
//             }

//             /*
//              * Don't create duplicate open alerts.
//              */
//             $alreadyAlerted = MaintenanceAlert::query()
//                 ->where('vehicle_id', $vehicle->id)
//                 ->where('maintenance_schedule_id', $schedule->id)
//                 ->where('acknowledged', false)
//                 ->exists();

//             if ($alreadyAlerted) {
//                 continue;
//             }

//             $reasons = [];

//             if ($distanceDue) {
//                 $reasons[] = 'distance interval reached';
//             }

//             if ($timeDue) {
//                 $reasons[] = 'time interval reached';
//             }

//             MaintenanceAlert::create([
//                 'vehicle_id' => $vehicle->id,
//                 'maintenance_schedule_id' => $schedule->id,
//                 'title' => "{$schedule->name} Due",
//                 'description' =>
//                     "Maintenance due because " .
//                     implode(' and ', $reasons) . '.',
//                 'alerted_at' => now(),
//                 'acknowledged' => false,
//             ]);
//         }

//         $this->info('Maintenance schedules checked.');

//         return self::SUCCESS;
//     }
// }

class CheckMaintenanceSchedules extends Command
{
    protected $signature = 'maintenance:check';

    protected $description = 'Check vehicles for due maintenance schedules';

    public function handle(): int
    {
        $schedules = MaintenanceSchedule::query()
            ->where('active', true)
            ->with([
                'vehicle.trackerState',
            ])
            ->get();

        foreach ($schedules as $schedule) {
            $vehicle = $schedule->vehicle;

            if (! $vehicle) {
                continue;
            }

            $latestRecord = MaintenanceRecord::query()
                ->where('vehicle_id', $vehicle->id)
                ->where('maintenance_schedule_id', $schedule->id)
                ->latest('maintained_at')
                ->first();

            /*
             * Backfill the schedule baseline only when there is
             * no maintenance history for this schedule.
             */
            if (! $latestRecord) {
                $baselineOdometer =
                    $vehicle->trackerState?->odometer
                    ?? $vehicle->mileage;

                $baselineDate =
                    $schedule->baseline_started_at
                    ?? $schedule->created_at
                    ?? now();

                if (
                    $schedule->baseline_odometer_km === null ||
                    $schedule->baseline_started_at === null
                ) {
                    $schedule->update([
                        'baseline_odometer_km' =>
                            $schedule->baseline_odometer_km
                            ?? $baselineOdometer,

                        'baseline_started_at' =>
                            $schedule->baseline_started_at
                            ?? $baselineDate,
                    ]);
                }
            }

            /*
             * Most recent maintenance record takes priority.
             * Otherwise use the schedule baseline.
             */
            $baselineOdometer =
                $latestRecord?->odometer_km
                ?? $schedule->baseline_odometer_km;

            $baselineDate =
                $latestRecord?->maintained_at
                ?? $schedule->baseline_started_at;

            $currentOdometer =
                $vehicle->trackerState?->odometer
                ?? $vehicle->mileage;

            $distanceDue = false;
            $timeDue = false;

            /*
             * Distance check
             */
            if (
                $schedule->distance_interval_km !== null &&
                $currentOdometer !== null &&
                $baselineOdometer !== null
            ) {
                $distanceTravelled =
                    $currentOdometer - $baselineOdometer;

                $distanceDue =
                    $distanceTravelled >=
                    $schedule->distance_interval_km;
            }

            /*
             * Time check
             */
            if (
                $schedule->time_interval_days !== null &&
                $baselineDate !== null
            ) {
                $daysElapsed =
                    $baselineDate->diffInDays(now());

                $timeDue =
                    $daysElapsed >=
                    $schedule->time_interval_days;
            }

            /*
             * Nothing is due.
             */
            if (! $distanceDue && ! $timeDue) {
                continue;
            }

            /*
             * Don't create duplicate open alerts.
             */
            $alreadyAlerted = MaintenanceAlert::query()
                ->where('vehicle_id', $vehicle->id)
                ->where('maintenance_schedule_id', $schedule->id)
                ->where('acknowledged', false)
                ->exists();

            if ($alreadyAlerted) {
                continue;
            }

            $reasons = [];

            if ($distanceDue) {
                $reasons[] = 'distance interval reached';
            }

            if ($timeDue) {
                $reasons[] = 'time interval reached';
            }

            MaintenanceAlert::create([
                'vehicle_id' => $vehicle->id,
                'maintenance_schedule_id' => $schedule->id,
                'title' => "{$schedule->name} Due",
                'description' =>
                    'Maintenance due because ' .
                    implode(' and ', $reasons) . '.',
                'alerted_at' => now(),
                'acknowledged' => false,
            ]);
        }

        $this->info('Maintenance schedules checked.');

        return self::SUCCESS;
    }
}
