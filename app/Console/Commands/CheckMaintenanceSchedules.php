<?php

namespace App\Console\Commands;

use App\Models\MaintenanceAlert;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceSchedule;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

class CheckMaintenanceSchedules extends Command
{
    protected $signature = 'maintenance:check';

    protected $description = 'Check vehicles for due maintenance schedules';

    public function handle(): int
    {
        $schedules = MaintenanceSchedule::query()
            ->where('active', true)
            ->with(['vehicle.trackerState'])
            ->get();

        foreach ($schedules as $schedule) {
            $vehicle = $schedule->vehicle;

            if (! $vehicle) {
                continue;
            }

            $currentOdometer = $vehicle->trackerState?->odometer ?? $vehicle->mileage;
            $currentEngineHours = $vehicle->trackerState?->engine_hours;

            $latestRecord = MaintenanceRecord::query()
                ->where('vehicle_id', $vehicle->id)
                ->where('maintenance_schedule_id', $schedule->id)
                ->latest('maintained_at')
                ->first();

            // Backfill baseline ONLY when a valid > 0 odometer reading is available,
            // or self-heal if baseline_odometer_km was previously saved as 0 while vehicle has > 1,000 km
            if (! $latestRecord) {
                $needsOdometerBackfill = ($schedule->baseline_odometer_km === null || ($schedule->baseline_odometer_km == 0 && $currentOdometer > 1000))
                    && $currentOdometer !== null
                    && $currentOdometer > 0;

                $needsDateBackfill = $schedule->baseline_started_at === null;

                if ($needsOdometerBackfill || $needsDateBackfill) {
                    $schedule->update([
                        'baseline_odometer_km' => $needsOdometerBackfill
                            ? (int) round($currentOdometer)
                            : $schedule->baseline_odometer_km,
                        'baseline_engine_hours' => $schedule->baseline_engine_hours ?? $currentEngineHours,
                        'baseline_started_at' => $schedule->baseline_started_at ?? $schedule->created_at ?? now(),
                    ]);
                }
            }

            $lastServiceOdometer = $latestRecord?->odometer_km ?? $schedule->baseline_odometer_km;
            $lastServiceDate = $latestRecord?->maintained_at ?? $schedule->baseline_started_at;
            $lastServiceHours = $latestRecord?->engine_hours ?? $schedule->baseline_engine_hours;

            $distanceTrigger = false;
            $timeTrigger = false;
            $hoursTrigger = false;
            $isOverdue = false;
            $reasons = [];

            // 1. Distance Check: Alert when Remaining Distance <= 500 km
            if ($schedule->distance_interval_km !== null && $currentOdometer !== null && $lastServiceOdometer !== null && $lastServiceOdometer > 0) {
                $distanceSinceService = (int) round($currentOdometer - $lastServiceOdometer);
                $remainingKm = (int) ($schedule->distance_interval_km - $distanceSinceService);

                if ($remainingKm <= 500) {
                    $distanceTrigger = true;
                    if ($remainingKm <= 0) {
                        $isOverdue = true;
                        $reasons[] = 'overdue by ' . abs($remainingKm) . ' km';
                    } else {
                        $reasons[] = "{$remainingKm} km remaining until service";
                    }
                }
            }

            // 2. Time Check: Alert when Remaining Days <= 14 days
            if ($schedule->time_interval_days !== null && $lastServiceDate !== null) {
                $daysElapsed = (int) $lastServiceDate->copy()->startOfDay()->diffInDays(now()->startOfDay(), false);
                $remainingDays = (int) ($schedule->time_interval_days - $daysElapsed);

                if ($remainingDays <= 14) {
                    $timeTrigger = true;
                    if ($remainingDays <= 0) {
                        $isOverdue = true;
                        $reasons[] = 'overdue by ' . abs($remainingDays) . ' days';
                    } else {
                        $reasons[] = "{$remainingDays} days remaining until service";
                    }
                }
            }

            // 3. Engine Hours Check: Alert when Remaining Hours <= 20 hrs
            if ($schedule->engine_hours_interval !== null && $currentEngineHours !== null && $lastServiceHours !== null && $lastServiceHours > 0) {
                $hoursSinceService = $currentEngineHours - $lastServiceHours;
                $remainingHours = $schedule->engine_hours_interval - $hoursSinceService;

                if ($remainingHours <= 20) {
                    $hoursTrigger = true;
                    if ($remainingHours <= 0) {
                        $isOverdue = true;
                        $reasons[] = 'overdue by ' . round(abs($remainingHours), 1) . ' engine hrs';
                    } else {
                        $reasons[] = round($remainingHours, 1) . ' engine hrs remaining';
                    }
                }
            }

            if (! $distanceTrigger && ! $timeTrigger && ! $hoursTrigger) {
                continue;
            }

            // Prevent duplicate unacknowledged alerts for the same schedule
            $alreadyAlerted = MaintenanceAlert::query()
                ->where('vehicle_id', $vehicle->id)
                ->where('maintenance_schedule_id', $schedule->id)
                ->where('acknowledged', false)
                ->exists();

            if ($alreadyAlerted) {
                continue;
            }

            $statusLabel = $isOverdue ? 'Overdue' : 'Due Soon';

            MaintenanceAlert::create([
                'vehicle_id' => $vehicle->id,
                'maintenance_schedule_id' => $schedule->id,
                'title' => "{$schedule->name} {$statusLabel}",
                'description' => 'Maintenance alert: ' . implode(', ', $reasons) . '.',
                'alerted_at' => now(),
                'acknowledged' => false,
            ]);
        }

        $this->info('Maintenance schedules checked.');

        return self::SUCCESS;
    }
}
