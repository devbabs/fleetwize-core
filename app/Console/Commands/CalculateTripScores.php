<?php

namespace App\Console\Commands;

use App\Models\VehicleTrip;
use App\Models\VehicleTripScore;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

class CalculateTripScores extends Command
{
    protected $signature = 'tracker:calculate-trip-scores
                            {--recalculate : Recalculate scores for all synced trips}
                            {--speed-limit= : Override max speed limit in km/h}';

    protected $description = 'Calculate trip scores across Harsh Braking, Harsh Acceleration, Speeding, Hard Cornering, and Idling';

    public function handle(): int
    {
        $processed = 0;

        $query = VehicleTrip::query()
            ->with(['routePoints', 'vehicle'])
            ->whereNotNull('route_synced_at');

        if (! $this->option('recalculate')) {
            $query->whereNull('score_calculated_at');
        }

        $query->chunkById(100, function ($trips) use (&$processed) {
            foreach ($trips as $trip) {
                try {
                    $this->calculateTripScore($trip);
                    $processed++;
                } catch (\Throwable $e) {
                    report($e);
                    $this->error("Trip {$trip->id} failed: {$e->getMessage()}");
                }
            }
        });

        $this->info("Calculated scores for {$processed} trips.");

        return self::SUCCESS;
    }

    protected function calculateTripScore(VehicleTrip $trip): void
    {
        $points = $trip->routePoints
            ->sortBy('fix_time')
            ->values();

        $speedLimit = (float) ($this->option('speed-limit')
            ?? $trip->vehicle?->speed_limit
            ?? 85);

        $harshBraking = 0;
        $harshAcceleration = 0;
        $hardCornering = 0;
        $idleSeconds = 0;
        $peakSpeedKmh = 0.0;

        $previousPoint = null;

        foreach ($points as $point) {
            $rawAttr = is_array($point->raw_payload)
                ? ($point->raw_payload['attributes'] ?? [])
                : [];

            $gpsSpeed = (float) ($point->speed_kmh ?? 0);
            $canSpeed = isset($rawAttr['io81'])
                ? (float) $rawAttr['io81']
                : (float) ($point->obd_speed_kmh ?? 0);
            $speed = max($gpsSpeed, $canSpeed);

            if ($speed > $peakSpeedKmh) {
                $peakSpeedKmh = $speed;
            }

            // 1. Harsh event deltas / Teltonika io253 / alarm attributes
            $accelAdded = 0;
            $brakeAdded = 0;
            $cornerAdded = 0;

            if ($previousPoint) {
                $accelAdded += $this->counterDelta($previousPoint->hard_acceleration_count, $point->hard_acceleration_count);
                $brakeAdded += $this->counterDelta($previousPoint->hard_deceleration_count, $point->hard_deceleration_count);
                $cornerAdded += $this->counterDelta($previousPoint->hard_cornering_count, $point->hard_cornering_count);
            }

            $greenDrivingType = isset($rawAttr['io253']) ? (int) $rawAttr['io253'] : null;
            $alarmAttr = $rawAttr['alarm'] ?? null;

            if ($accelAdded === 0 && ($greenDrivingType === 1 || $alarmAttr === 'hardAcceleration')) {
                $accelAdded = 1;
            }
            if ($brakeAdded === 0 && ($greenDrivingType === 2 || $alarmAttr === 'hardBraking')) {
                $brakeAdded = 1;
            }
            if ($cornerAdded === 0 && ($greenDrivingType === 3 || $alarmAttr === 'hardCornering')) {
                $cornerAdded = 1;
            }

            // Kinematic fallback across tight consecutive points (1s to 30s)
            if ($previousPoint && $accelAdded === 0 && $brakeAdded === 0 && $cornerAdded === 0) {
                $dt = $this->rawIntervalSeconds($previousPoint, $point);

                if ($dt >= 1 && $dt <= 30) {
                    $prevRawAttr = is_array($previousPoint->raw_payload)
                        ? ($previousPoint->raw_payload['attributes'] ?? [])
                        : [];
                    $prevSpeed = max(
                        (float) ($previousPoint->speed_kmh ?? 0),
                        isset($prevRawAttr['io81']) ? (float) $prevRawAttr['io81'] : (float) ($previousPoint->obd_speed_kmh ?? 0)
                    );

                    $longitudinalAccelMs2 = (($speed - $prevSpeed) / 3.6) / $dt;

                    if ($longitudinalAccelMs2 >= 2.8) {
                        $accelAdded = 1;
                    } elseif ($longitudinalAccelMs2 <= -3.2) {
                        $brakeAdded = 1;
                    }

                    $avgSpeedMs = (($speed + $prevSpeed) / 2.0) / 3.6;
                    if ($avgSpeedMs >= 8.33 && $previousPoint->course !== null && $point->course !== null) {
                        $headingDiffDeg = abs((float) $point->course - (float) $previousPoint->course);
                        if ($headingDiffDeg > 180) {
                            $headingDiffDeg = 360 - $headingDiffDeg;
                        }
                        $omegaRadSec = deg2rad($headingDiffDeg) / $dt;
                        $lateralAccelMs2 = $avgSpeedMs * $omegaRadSec;

                        if ($lateralAccelMs2 >= 3.2 && $headingDiffDeg >= 25) {
                            $cornerAdded = 1;
                        }
                    }
                }
            }

            $harshAcceleration += $accelAdded;
            $harshBraking += $brakeAdded;
            $hardCornering += $cornerAdded;

            // 2. Idling: ignition ON and speed < 2 km/h between consecutive points
            if ($previousPoint) {
                $prevRawAttr = is_array($previousPoint->raw_payload)
                    ? ($previousPoint->raw_payload['attributes'] ?? [])
                    : [];
                $previousSpeed = max(
                    (float) ($previousPoint->speed_kmh ?? 0),
                    isset($prevRawAttr['io81']) ? (float) $prevRawAttr['io81'] : (float) ($previousPoint->obd_speed_kmh ?? 0)
                );
                $previousIgnition = $previousPoint->ignition
                    ?? (isset($prevRawAttr['ignition']) ? (bool) $prevRawAttr['ignition'] : true);

                $seconds = $this->intervalSeconds($previousPoint, $point);

                if ($previousIgnition === true && $previousSpeed < 2.0) {
                    $idleSeconds += $seconds;
                }
            }

            $previousPoint = $point;
        }

        // Guarantee values for all 5 behaviours on every trip
        $effectiveMaxSpeed = max(
            round($peakSpeedKmh, 2),
            (float) ($trip->max_speed_km_per_hr ?? 0),
            (float) ($trip->max_speed ?? 0)
        );

        $idleMinutes = $points->count() >= 2
            ? (int) round($idleSeconds / 60)
            : (int) ($trip->idle_minutes ?? 0);

        $speedExcessKmh = max(0.0, round($effectiveMaxSpeed - $speedLimit, 2));
        $speedingViolations = $speedExcessKmh > 0 ? 1 : 0;

        // 5 core behaviour scores (0 - 100)
        $harshBrakingScore = max(0, min(100, 100 - ($harshBraking * 5)));
        $harshAccelerationScore = max(0, min(100, 100 - ($harshAcceleration * 4)));
        $speedingPenalty = $speedExcessKmh > 0 ? (10 + (int) floor($speedExcessKmh)) : 0;
        $speedingScore = max(0, min(100, 100 - $speedingPenalty));
        $hardCorneringScore = max(0, min(100, 100 - ($hardCornering * 4)));
        $excessIdleMinutes = max(0, $idleMinutes - 10);
        $idlingScore = max(0, min(100, 100 - (int) floor($excessIdleMinutes / 5)));

        // Equal 20% weighting across the 5 core behaviours
        $score = round(
            ($harshBrakingScore * 0.20)
            + ($harshAccelerationScore * 0.20)
            + ($speedingScore * 0.20)
            + ($hardCorneringScore * 0.20)
            + ($idlingScore * 0.20),
            2
        );

        $grade = $this->grade($score);

        $driverId = $trip->company_user_id
            ?? $trip->vehicle?->driver_id
            ?? $trip->vehicle?->company_user_id;

        $trip->update([
            'company_user_id' => $driverId,
            'score' => $score,
            'grade' => $grade,

            // Five core behaviour metrics
            'harsh_braking_events' => $harshBraking,
            'harsh_acceleration_events' => $harshAcceleration,
            'overspeed_events' => $speedingViolations,
            'harsh_cornering_events' => $hardCornering,
            'idle_minutes' => $idleMinutes,

            // Five core behaviour scores
            'harsh_braking_score' => $harshBrakingScore,
            'harsh_acceleration_score' => $harshAccelerationScore,
            'speeding_score' => $speedingScore,
            'hard_cornering_score' => $hardCorneringScore,
            'idling_score' => $idlingScore,

            'max_speed_km_per_hr' => $effectiveMaxSpeed,
            'max_speed' => $effectiveMaxSpeed,
            'score_calculated_at' => now(),
        ]);
    }

    protected function counterDelta(?int $previous, ?int $current): int
    {
        if ($previous === null || $current === null) {
            return 0;
        }

        if ($current < $previous) {
            return max(0, $current);
        }

        return $current - $previous;
    }

    protected function rawIntervalSeconds($previous, $current): int
    {
        if (! $previous->fix_time || ! $current->fix_time) {
            return 0;
        }

        return (int) abs(
            Carbon::parse($current->fix_time)->diffInSeconds(Carbon::parse($previous->fix_time))
        );
    }

    protected function intervalSeconds($previous, $current): int
    {
        return min(120, $this->rawIntervalSeconds($previous, $current));
    }

    protected function grade(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'F',
        };
    }
}
