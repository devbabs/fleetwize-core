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
                            {--speed-limit= : Override default max speed limit in km/h}';

    protected $description = 'Calculate trip scores from harsh braking, harsh acceleration, speeding, hard cornering, and idling';

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

        // Speed limit priority: CLI option -> vehicle speed_limit -> default 85 km/h
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

            // 1. Harsh events from counters / Teltonika io253 / alarm attributes
            $pointAccelAdded = 0;
            $pointBrakeAdded = 0;
            $pointCornerAdded = 0;

            if ($previousPoint) {
                $pointAccelAdded += $this->counterDelta(
                    $previousPoint->hard_acceleration_count,
                    $point->hard_acceleration_count
                );
                $pointBrakeAdded += $this->counterDelta(
                    $previousPoint->hard_deceleration_count,
                    $point->hard_deceleration_count
                );
                $pointCornerAdded += $this->counterDelta(
                    $previousPoint->hard_cornering_count,
                    $point->hard_cornering_count
                );
            }

            $greenDrivingType = isset($rawAttr['io253']) ? (int) $rawAttr['io253'] : null;
            $alarmAttr = $rawAttr['alarm'] ?? null;

            if ($pointAccelAdded === 0 && ($greenDrivingType === 1 || $alarmAttr === 'hardAcceleration')) {
                $pointAccelAdded = 1;
            }
            if ($pointBrakeAdded === 0 && ($greenDrivingType === 2 || $alarmAttr === 'hardBraking')) {
                $pointBrakeAdded = 1;
            }
            if ($pointCornerAdded === 0 && ($greenDrivingType === 3 || $alarmAttr === 'hardCornering')) {
                $pointCornerAdded = 1;
            }

            // 2. Kinematics fallback across consecutive points (1s to 60s)
            if ($previousPoint && $pointAccelAdded === 0 && $pointBrakeAdded === 0 && $pointCornerAdded === 0) {
                $dt = $this->rawIntervalSeconds($previousPoint, $point);

                if ($dt >= 1 && $dt <= 60) {
                    $prevRawAttr = is_array($previousPoint->raw_payload)
                        ? ($previousPoint->raw_payload['attributes'] ?? [])
                        : [];
                    $prevSpeed = max(
                        (float) ($previousPoint->speed_kmh ?? 0),
                        isset($prevRawAttr['io81']) ? (float) $prevRawAttr['io81'] : (float) ($previousPoint->obd_speed_kmh ?? 0)
                    );

                    $longitudinalAccelMs2 = (($speed - $prevSpeed) / 3.6) / $dt;

                    if ($longitudinalAccelMs2 >= 2.8) {
                        $pointAccelAdded = 1;
                    } elseif ($longitudinalAccelMs2 <= -3.2) {
                        $pointBrakeAdded = 1;
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
                            $pointCornerAdded = 1;
                        }
                    }
                }
            }

            $harshAcceleration += $pointAccelAdded;
            $harshBraking += $pointBrakeAdded;
            $hardCornering += $pointCornerAdded;

            // 3. Idling: ignition ON and speed < 2 km/h between consecutive points
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

        // Ensure we always have a max speed value for the trip
        $effectiveMaxSpeed = max(
            round($peakSpeedKmh, 2),
            (float) ($trip->max_speed_km_per_hr ?? 0),
            (float) ($trip->max_speed ?? 0)
        );

        // Ensure we always have an idle_minutes value (fallback to existing trip value if route had no intervals)
        $idleMinutes = $points->count() >= 2
            ? (int) round($idleSeconds / 60)
            : (int) ($trip->idle_minutes ?? 0);

        // Speeding calculation based on max speed over the configured speed limit
        $speedExcessKmh = max(0.0, round($effectiveMaxSpeed - $speedLimit, 2));
        $overspeedEvents = $speedExcessKmh > 0 ? 1 : 0;

        // Individual behaviour scores (0 - 100)
        $harshBrakingScore = max(0, min(100, 100 - ($harshBraking * 5)));
        $harshAccelerationScore = max(0, min(100, 100 - ($harshAcceleration * 4)));
        $hardCorneringScore = max(0, min(100, 100 - ($hardCornering * 4)));

        // Speeding score: penalize based on how far max speed exceeded the limit
        // e.g., -10 base for exceeding limit + -1 per km/h over limit
        $speedingPenalty = $speedExcessKmh > 0
            ? (10 + (int) floor($speedExcessKmh))
            : 0;
        $speedingScore = max(0, min(100, 100 - $speedingPenalty));

        // Idling score: 10-minute grace period, then -1 pt per 5 mins over
        $excessIdleMinutes = max(0, $idleMinutes - 10);
        $idlingScore = max(0, min(100, 100 - (int) floor($excessIdleMinutes / 5)));

        // Overall trip score (equal 20% across the 5 core behaviours; adjust weights as needed)
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

            // Core 5 behaviour event/metric values (guaranteed present)
            'harsh_braking_events' => $harshBraking,
            'harsh_acceleration_events' => $harshAcceleration,
            'overspeed_events' => $overspeedEvents,
            'harsh_cornering_events' => $hardCornering,
            'idle_minutes' => $idleMinutes,

            // Pillar / sub-scores (mapped cleanly to existing columns)
            'risk_score' => round(($harshBrakingScore + $hardCorneringScore) / 2, 2),
            'speeding_score' => $speedingScore,
            'eco_score' => round(($harshAccelerationScore + $idlingScore) / 2, 2),
            'distraction_score' => $idlingScore,
            'fatigue_score' => 100,

            // Speed fields
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
