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
                            {--recalculate : Recalculate scores for all synced trips}';

    protected $description = 'Calculate driver behaviour and safety scores across the 5 Pillars for completed trips';

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

        // If a micro-trip has fewer than 2 points, mark it scored with baseline 100
        // so the cron job does not re-query it forever every 15 minutes.
        if ($points->count() < 2) {
            $trip->update([
                'company_user_id' => $trip->company_user_id ?? $trip->vehicle?->driver_id ?? $trip->vehicle?->company_user_id,
                'score' => 100,
                'grade' => 'A',
                'risk_score' => 100,
                'speeding_score' => 100,
                'eco_score' => 100,
                'fatigue_score' => 100,
                'distraction_score' => 100,
                'safety_score' => 100,
                'efficiency_score' => 100,
                'score_calculated_at' => now(),
            ]);

            return;
        }

        // Default Nigerian commercial fleet thresholds (80 km/h urban/arterial, 100 km/h highway/severe)
        // Uses vehicle custom speed_limit if configured on the vehicle model
        $speedLimit = (float) ($trip->vehicle?->speed_limit ?? 85);
        $severeSpeedLimit = $speedLimit + 20;

        $overspeedEvents = 0;
        $severeOverspeedEvents = 0;
        $overspeedDurationSeconds = 0;

        $harshAcceleration = 0;
        $harshBraking = 0;
        $harshCornering = 0;

        $highEngineLoadEvents = 0;
        $idleSeconds = 0;

        $continuousDrivingSeconds = 0;
        $maxContinuousDrivingSeconds = 0;
        $fatigueEvents = 0;

        $previousPoint = null;

        $isOverspeeding = false;
        $overspeedStartTime = null;
        $isSevereOverspeeding = false;
        $isHighEngineLoad = false;

        foreach ($points as $point) {
            $rawAttr = is_array($point->raw_payload)
                ? ($point->raw_payload['attributes'] ?? [])
                : [];

            // Resolve speed: prefer OBD/CAN wheel speed (io81 / obd_speed_kmh) or GPS speed_kmh
            $gpsSpeed = (float) ($point->speed_kmh ?? 0);
            $canSpeed = isset($rawAttr['io81'])
                ? (float) $rawAttr['io81']
                : (float) ($point->obd_speed_kmh ?? 0);
            $speed = max($gpsSpeed, $canSpeed);

            // Resolve ignition (column or Teltonika io1)
            $ignition = $point->ignition ?? (isset($rawAttr['ignition']) ? (bool) $rawAttr['ignition'] : true);

            /*
            |--------------------------------------------------------------------------
            | 1. HARSH DRIVING (JT808 Counters + Teltonika io253 + Kinematics Fallback)
            |--------------------------------------------------------------------------
            */
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

            // Teltonika Green Driving hardware event (AVL ID 253: 1 = Accel, 2 = Brake, 3 = Cornering)
            // or Traccar alarm attribute
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

            // Kinematic fallback when hardware counters/io253 did not fire on this interval
            if ($previousPoint && $pointAccelAdded === 0 && $pointBrakeAdded === 0 && $pointCornerAdded === 0) {
                $dt = $this->rawIntervalSeconds($previousPoint, $point);

                // Only evaluate kinematics across tight consecutive pings (1s to 30s)
                if ($dt >= 1 && $dt <= 30) {
                    $prevRawAttr = is_array($previousPoint->raw_payload)
                        ? ($previousPoint->raw_payload['attributes'] ?? [])
                        : [];
                    $prevSpeed = max(
                        (float) ($previousPoint->speed_kmh ?? 0),
                        isset($prevRawAttr['io81']) ? (float) $prevRawAttr['io81'] : (float) ($previousPoint->obd_speed_kmh ?? 0)
                    );

                    // Convert km/h delta to m/s^2 longitudinal acceleration
                    $deltaSpeedMs = ($speed - $prevSpeed) / 3.6;
                    $longitudinalAccelMs2 = $deltaSpeedMs / $dt;

                    if ($longitudinalAccelMs2 >= 2.8) {
                        $pointAccelAdded = 1; // >= +0.29g rapid launch
                    } elseif ($longitudinalAccelMs2 <= -3.2) {
                        $pointBrakeAdded = 1; // <= -0.33g harsh brake
                    }

                    // Centripetal cornering check at speeds >= 30 km/h
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
            $harshCornering += $pointCornerAdded;

            /*
            |--------------------------------------------------------------------------
            | 2. SPEEDING (> 15 seconds sustained)
            |--------------------------------------------------------------------------
            */
            $currentlyOverspeeding = $speed > $speedLimit;
            $currentlySevereOverspeeding = $speed > $severeSpeedLimit;

            if ($currentlyOverspeeding && ! $isOverspeeding) {
                $overspeedStartTime = $this->pointTime($point);
            }

            if (! $currentlyOverspeeding && $isOverspeeding) {
                if ($overspeedStartTime) {
                    $endTime = $this->pointTime($point);
                    $duration = (int) abs($endTime->diffInSeconds($overspeedStartTime));

                    if ($duration >= 15) {
                        $overspeedEvents++;
                        $overspeedDurationSeconds += $duration;
                    }
                }

                $overspeedStartTime = null;
            }

            if ($currentlySevereOverspeeding && ! $isSevereOverspeeding) {
                $severeOverspeedEvents++;
            }

            $isOverspeeding = $currentlyOverspeeding;
            $isSevereOverspeeding = $currentlySevereOverspeeding;

            /*
            |--------------------------------------------------------------------------
            | 3. ECO — HIGH ENGINE LOAD (> 85%) OR HIGH RPM (> 3,500 RPM)
            |--------------------------------------------------------------------------
            */
            $engineLoad = (float) ($point->engine_load ?? $rawAttr['io82'] ?? 0);
            $engineRpm = (int) ($point->rpm ?? $rawAttr['io85'] ?? 0);

            $currentlyHighLoad = ($engineLoad > 85) || ($engineRpm > 3500);

            if ($currentlyHighLoad && ! $isHighEngineLoad) {
                $highEngineLoadEvents++;
            }

            $isHighEngineLoad = $currentlyHighLoad;

            /*
            |--------------------------------------------------------------------------
            | 4. IDLE / DISTRACTION (Ignition ON + Stationary Speed < 2 km/h)
            |--------------------------------------------------------------------------
            */
            if ($previousPoint) {
                $prevRawAttr = is_array($previousPoint->raw_payload)
                    ? ($previousPoint->raw_payload['attributes'] ?? [])
                    : [];
                $previousSpeed = max(
                    (float) ($previousPoint->speed_kmh ?? 0),
                    isset($prevRawAttr['io81']) ? (float) $prevRawAttr['io81'] : 0.0
                );
                $previousIgnition = $previousPoint->ignition
                    ?? (isset($prevRawAttr['ignition']) ? (bool) $prevRawAttr['ignition'] : true);

                $seconds = $this->intervalSeconds($previousPoint, $point);

                if ($previousIgnition === true && $previousSpeed < 2.0) {
                    $idleSeconds += $seconds;
                }

                /*
                |--------------------------------------------------------------------------
                | 5. FATIGUE (Continuous driving > 4.5 hours without ignition OFF)
                |--------------------------------------------------------------------------
                */
                if ($previousIgnition === true && $previousSpeed >= 2.0) {
                    $continuousDrivingSeconds += $seconds;
                    $maxContinuousDrivingSeconds = max($maxContinuousDrivingSeconds, $continuousDrivingSeconds);

                    $fatigueThresholdSeconds = (int) (4.5 * 3600); // 16,200s
                    $prevBucket = intdiv(max(0, $continuousDrivingSeconds - $seconds), $fatigueThresholdSeconds);
                    $currBucket = intdiv($continuousDrivingSeconds, $fatigueThresholdSeconds);

                    if ($currBucket > $prevBucket) {
                        $fatigueEvents++;
                    }
                } elseif ($previousIgnition === false) {
                    $continuousDrivingSeconds = 0;
                }
            }

            $previousPoint = $point;
        }

        // Close an overspeed event that remained active up to the final route point
        if ($isOverspeeding && $overspeedStartTime) {
            $endTime = $this->pointTime($points->last());
            $duration = (int) abs($endTime->diffInSeconds($overspeedStartTime));

            if ($duration >= 15) {
                $overspeedEvents++;
                $overspeedDurationSeconds += $duration;
            }
        }

        $idleMinutes = (int) round($idleSeconds / 60);

        /*
        |--------------------------------------------------------------------------
        | Calculate the 5 Pillar Scores (0 - 100)
        |--------------------------------------------------------------------------
        */

        // 1. RISK: Harsh braking (-5), Harsh cornering (-3)
        $riskScore = max(0, min(100, 100 - ($harshBraking * 5) - ($harshCornering * 3)));

        // 2. SPEEDING: Sustained overspeed (-10), Severe overspeed spike (-15)
        $speedingScore = max(0, min(100, 100 - ($overspeedEvents * 10) - ($severeOverspeedEvents * 15)));

        // 3. ECO: Harsh acceleration (-3), High engine load / RPM surge (-2)
        $ecoScore = max(0, min(100, 100 - ($harshAcceleration * 3) - ($highEngineLoadEvents * 2)));

        // 4. FATIGUE: Continuous driving > 4.5 hours (-15)
        $fatigueScore = max(0, min(100, 100 - ($fatigueEvents * 15)));

        // 5. DISTRACTION: First 10 mins idle grace; -1 pt per 5 mins thereafter
        $excessIdleMinutes = max(0, $idleMinutes - 10);
        $distractionScore = max(0, min(100, 100 - (int) floor($excessIdleMinutes / 5)));

        /*
        |--------------------------------------------------------------------------
        | Weighted Overall Score (Matches AggregateDriverScores weights)
        | Risk 25% | Speeding 25% | Eco 20% | Fatigue 15% | Distraction 15%
        |--------------------------------------------------------------------------
        */
        $score = round(
            ($riskScore * 0.25)
            + ($speedingScore * 0.25)
            + ($ecoScore * 0.20)
            + ($fatigueScore * 0.15)
            + ($distractionScore * 0.15),
            2
        );

        $grade = $this->grade($score);

        // Ensure trip is linked to the vehicle's assigned driver if not already set
        $driverId = $trip->company_user_id
            ?? $trip->vehicle?->driver_id
            ?? $trip->vehicle?->company_user_id;

        // Extract Teltonika CAN start/end odometer (io87) and exact fuel burned (io107) across the trip
        $firstPoint = $points->first();
        $lastPoint = $points->last();

        $startCanOdo = $firstPoint->obd_odometer
            ?? (isset($firstPoint->raw_payload['attributes']['io87'])
                ? round(((float) $firstPoint->raw_payload['attributes']['io87']) / 1000.0, 1)
                : null);

        $endCanOdo = $lastPoint->obd_odometer
            ?? (isset($lastPoint->raw_payload['attributes']['io87'])
                ? round(((float) $lastPoint->raw_payload['attributes']['io87']) / 1000.0, 1)
                : null);

        $startFuelConsumed = $firstPoint->fuel_consumption
            ?? (isset($firstPoint->raw_payload['attributes']['io107'])
                ? round(((float) $firstPoint->raw_payload['attributes']['io107']) * 0.1, 1)
                : null);

        $endFuelConsumed = $lastPoint->fuel_consumption
            ?? (isset($lastPoint->raw_payload['attributes']['io107'])
                ? round(((float) $lastPoint->raw_payload['attributes']['io107']) * 0.1, 1)
                : null);

        $tripFuelBurned = ($startFuelConsumed !== null && $endFuelConsumed !== null && $endFuelConsumed >= $startFuelConsumed)
            ? round($endFuelConsumed - $startFuelConsumed, 2)
            : $trip->fuel_consumed;

        // If CAN start & end odometer are valid, compute exact CAN trip distance as fallback/refinement
        $canTripDistanceKm = ($startCanOdo !== null && $endCanOdo !== null && $endCanOdo >= $startCanOdo)
            ? round($endCanOdo - $startCanOdo, 2)
            : null;

        $effectiveDistanceKm = ($trip->distance_km !== null && $trip->distance_km > 0)
            ? (float) $trip->distance_km
            : ($canTripDistanceKm ?? 0.0);

        // Ensure trip is linked to the vehicle's assigned driver if not already set
        $driverId = $trip->company_user_id
            ?? $trip->vehicle?->driver_id
            ?? $trip->vehicle?->company_user_id;

        $trip->update([
            'company_user_id' => $driverId,
            'score' => $score,
            'grade' => $grade,

            // Five Pillars
            'risk_score' => $riskScore,
            'speeding_score' => $speedingScore,
            'eco_score' => $ecoScore,
            'fatigue_score' => $fatigueScore,
            'distraction_score' => $distractionScore,

            // Backward compatibility
            'safety_score' => round(($riskScore + $speedingScore) / 2, 2),
            'efficiency_score' => round(($ecoScore + $distractionScore) / 2, 2),

            // Event counters
            'overspeed_events' => $overspeedEvents,
            'severe_overspeed_events' => $severeOverspeedEvents,
            'overspeed_duration_seconds' => $overspeedDurationSeconds,
            'harsh_acceleration_events' => $harshAcceleration,
            'harsh_braking_events' => $harshBraking,
            'harsh_cornering_events' => $harshCornering,
            'high_engine_load_events' => $highEngineLoadEvents,
            'fatigue_events' => $fatigueEvents,
            'continuous_driving_seconds' => $maxContinuousDrivingSeconds,
            'idle_minutes' => $idleMinutes,

            // CAN Odometer & Fuel Consumed (from $startCanOdo, $endCanOdo, $tripFuelBurned)
            'start_odometer' => $startCanOdo ?? $trip->start_odometer,
            'end_odometer' => $endCanOdo ?? $trip->end_odometer,
            'fuel_consumed' => $tripFuelBurned,

            // Trip summary metrics
            'distance_km' => $effectiveDistanceKm,
            'trip_distance_km' => $effectiveDistanceKm,
            'trip_duration_seconds' => $trip->duration_seconds,
            'average_speed' => $trip->average_speed_km_per_hr,
            'max_speed' => $trip->max_speed_km_per_hr,

            'score_calculated_at' => now(),
        ]);

        $this->line(
            "Trip {$trip->id}: Score {$score} | Risk {$riskScore} | Speed {$speedingScore} | Eco {$ecoScore} | Fatigue {$fatigueScore} | Distraction {$distractionScore} | Grade {$grade}"
        );
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

    protected function pointTime($point): Carbon
    {
        return Carbon::parse($point->fix_time);
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
