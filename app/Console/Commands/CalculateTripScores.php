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
    protected $signature = 'tracker:calculate-trip-scores';

    protected $description =
        'Calculate driver behaviour and safety scores for completed trips';

    public function handle(): int
    {
        $processed = 0;

        VehicleTrip::query()
            ->with('routePoints')
            ->whereNotNull('route_synced_at')
            ->whereNull('score_calculated_at')
            ->chunkById(100, function ($trips) use (&$processed) {

                foreach ($trips as $trip) {

                    if ($trip->routePoints->count() < 2) {
                        continue;
                    }

                    try {

                        $this->calculateTripScore($trip);

                        $processed++;

                    } catch (\Throwable $e) {

                        report($e);

                        $this->error(
                            "Trip {$trip->id} failed: {$e->getMessage()}"
                        );
                    }
                }
            });

        $this->info(
            "Calculated scores for {$processed} trips."
        );

        return self::SUCCESS;
    }

    protected function calculateTripScore(VehicleTrip $trip): void
    {
        $points = $trip->routePoints
            ->sortBy('fix_time')
            ->values();

        if ($points->count() < 2) {
            return;
        }

        $speedLimit = 120; // km/h
        $severeSpeedLimit = 140; // km/h

        $overspeedEvents = 0;
        $severeOverspeedEvents = 0;
        $overspeedDurationSeconds = 0;

        $harshAcceleration = 0;
        $harshBraking = 0;
        $harshCornering = 0;

        $highEngineLoadEvents = 0;

        $idleSeconds = 0;

        $continuousDrivingSeconds = 0;
        $fatigueEvents = 0;

        /*
        |--------------------------------------------------------------------------
        | Event counters
        |--------------------------------------------------------------------------
        */

        $previousPoint = null;

        $isOverspeeding = false;
        $overspeedStartTime = null;

        $isSevereOverspeeding = false;

        $isHighEngineLoad = false;

        foreach ($points as $point) {

            /*
            |--------------------------------------------------------------------------
            | 1. Cumulative harsh-driving counters
            |--------------------------------------------------------------------------
            |
            | Traccar counters are cumulative, so calculate the delta between
            | consecutive route points rather than simply first -> last.
            |
            */

            if ($previousPoint) {

                $harshAcceleration += $this->counterDelta(
                    $previousPoint->hard_acceleration_count,
                    $point->hard_acceleration_count
                );

                $harshBraking += $this->counterDelta(
                    $previousPoint->hard_deceleration_count,
                    $point->hard_deceleration_count
                );

                $harshCornering += $this->counterDelta(
                    $previousPoint->hard_cornering_count,
                    $point->hard_cornering_count
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Speed
            |--------------------------------------------------------------------------
            */

            $speed = (float) ($point->speed_kmh ?? 0);

            /*
            |--------------------------------------------------------------------------
            | 2. SPEEDING
            |--------------------------------------------------------------------------
            |
            | We only count an overspeed incident when it lasts more than
            | 15 seconds.
            |
            */

            $currentlyOverspeeding =
                $speed > $speedLimit;

            $currentlySevereOverspeeding =
                $speed > $severeSpeedLimit;

            if ($currentlyOverspeeding && ! $isOverspeeding) {

                $overspeedStartTime = $this->pointTime($point);
            }

            if (! $currentlyOverspeeding && $isOverspeeding) {

                if ($overspeedStartTime) {

                    $endTime = $this->pointTime($point);

                    $duration = max(
                        0,
                        $endTime->diffInSeconds($overspeedStartTime)
                    );

                    if ($duration > 15) {
                        $overspeedEvents++;
                        $overspeedDurationSeconds += $duration;
                    }
                }

                $overspeedStartTime = null;
            }

            /*
            |--------------------------------------------------------------------------
            | Severe overspeed events
            |--------------------------------------------------------------------------
            */

            if (
                $currentlySevereOverspeeding
                && ! $isSevereOverspeeding
            ) {
                $severeOverspeedEvents++;
            }

            $isOverspeeding = $currentlyOverspeeding;
            $isSevereOverspeeding = $currentlySevereOverspeeding;

            /*
            |--------------------------------------------------------------------------
            | 3. ECO — HIGH ENGINE LOAD
            |--------------------------------------------------------------------------
            */

            $engineLoad = (float) (
                $point->engine_load ?? 0
            );

            $currentlyHighLoad =
                $engineLoad > 85;

            if (
                $currentlyHighLoad
                && ! $isHighEngineLoad
            ) {
                $highEngineLoadEvents++;
            }

            $isHighEngineLoad = $currentlyHighLoad;

            /*
            |--------------------------------------------------------------------------
            | 4. IDLE / DISTRACTION
            |--------------------------------------------------------------------------
            |
            | Ignition ON + vehicle speed 0.
            |
            */

            if ($previousPoint) {

                $previousSpeed =
                    (float) ($previousPoint->speed_kmh ?? 0);

                if (
                    $previousPoint->ignition === true
                    &&
                    $previousSpeed <= 0
                ) {

                    $seconds = $this->intervalSeconds(
                        $previousPoint,
                        $point
                    );

                    $idleSeconds += $seconds;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 5. FATIGUE
            |--------------------------------------------------------------------------
            |
            | Continuous driving is reset when ignition goes OFF.
            |
            */

            if ($previousPoint) {

                $seconds = $this->intervalSeconds(
                    $previousPoint,
                    $point
                );

                if (
                    $previousPoint->ignition === true
                    &&
                    (float) ($previousPoint->speed_kmh ?? 0) > 0
                ) {

                    $continuousDrivingSeconds += $seconds;

                    /*
                    | Count a fatigue event every time the continuous driving
                    | period crosses 4.5 hours.
                    */
                    if (
                        $continuousDrivingSeconds >=
                        (4.5 * 3600)
                        &&
                        $continuousDrivingSeconds - $seconds <
                        (4.5 * 3600)
                    ) {
                        $fatigueEvents++;
                    }

                } elseif (
                    $previousPoint->ignition === false
                ) {

                    $continuousDrivingSeconds = 0;
                }
            }

            $previousPoint = $point;
        }

        /*
        |--------------------------------------------------------------------------
        | Close an overspeed event that continues until the final point
        |--------------------------------------------------------------------------
        */

        if (
            $isOverspeeding
            && $overspeedStartTime
        ) {

            $endTime = $this->pointTime(
                $points->last()
            );

            $duration = max(
                0,
                $endTime->diffInSeconds(
                    $overspeedStartTime
                )
            );

            if ($duration > 15) {
                $overspeedEvents++;
                $overspeedDurationSeconds += $duration;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Convert idle seconds to minutes
        |--------------------------------------------------------------------------
        */

        $idleMinutes = (int) round(
            $idleSeconds / 60
        );

        /*
        |--------------------------------------------------------------------------
        | Calculate the five pillar scores
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | 1. RISK
        |--------------------------------------------------------------------------
        |
        | Harsh braking:     -5
        | Harsh cornering:  -3
        |
        */

        $riskScore = 100;

        $riskScore -= $harshBraking * 5;
        $riskScore -= $harshCornering * 3;

        $riskScore = max(
            0,
            min(100, $riskScore)
        );

        /*
        |--------------------------------------------------------------------------
        | 2. SPEEDING
        |--------------------------------------------------------------------------
        |
        | Each sustained overspeed incident (>15 sec): -10
        |
        */

        $speedingScore = 100;

        $speedingScore -= $overspeedEvents * 10;

        $speedingScore = max(
            0,
            min(100, $speedingScore)
        );

        /*
        |--------------------------------------------------------------------------
        | 3. ECO
        |--------------------------------------------------------------------------
        |
        | Harsh acceleration: -4
        | High engine load event: -2
        |
        */

        $ecoScore = 100;

        $ecoScore -= $harshAcceleration * 4;
        $ecoScore -= $highEngineLoadEvents * 2;

        $ecoScore = max(
            0,
            min(100, $ecoScore)
        );

        /*
        |--------------------------------------------------------------------------
        | 4. FATIGUE
        |--------------------------------------------------------------------------
        |
        | Continuous driving >4.5 hours: -15
        |
        */

        $fatigueScore = 100;

        $fatigueScore -= $fatigueEvents * 15;

        $fatigueScore = max(
            0,
            min(100, $fatigueScore)
        );

        /*
        |--------------------------------------------------------------------------
        | 5. DISTRACTION
        |--------------------------------------------------------------------------
        |
        | First 10 minutes of idle = grace period.
        |
        | Every additional 5 minutes = -1.
        |
        */

        $excessIdleMinutes = max(
            0,
            $idleMinutes - 10
        );

        $distractionScore = 100;

        $distractionScore -= floor(
            $excessIdleMinutes / 5
        );

        $distractionScore = max(
            0,
            min(100, $distractionScore)
        );

        /*
        |--------------------------------------------------------------------------
        | Overall score
        |--------------------------------------------------------------------------
        */

        $score = round(
            (
                $riskScore
                + $speedingScore
                + $ecoScore
                + $fatigueScore
                + $distractionScore
            ) / 5,
            2
        );

        $grade = $this->grade($score);

        /*
        |--------------------------------------------------------------------------
        | Persist
        |--------------------------------------------------------------------------
        */

        $trip->update([
            /*
            | Overall
            */
            'score' => $score,
            'grade' => $grade,

            /*
            | Five pillars
            */
            'risk_score' => $riskScore,
            'speeding_score' => $speedingScore,
            'eco_score' => $ecoScore,
            'fatigue_score' => $fatigueScore,
            'distraction_score' => $distractionScore,

            /*
            | Existing safety/efficiency fields
            |
            | Keep these temporarily for backward compatibility.
            */
            'safety_score' => round(
                ($riskScore + $speedingScore) / 2,
                2
            ),

            'efficiency_score' => round(
                ($ecoScore + $distractionScore) / 2,
                2
            ),

            /*
            | Event metrics
            */
            'overspeed_events' => $overspeedEvents,
            'severe_overspeed_events' => $severeOverspeedEvents,
            'overspeed_duration_seconds' => $overspeedDurationSeconds,

            'harsh_acceleration_events' => $harshAcceleration,
            'harsh_braking_events' => $harshBraking,
            'harsh_cornering_events' => $harshCornering,

            'high_engine_load_events' => $highEngineLoadEvents,

            'fatigue_events' => $fatigueEvents,
            'continuous_driving_seconds' =>
                $continuousDrivingSeconds,

            'idle_minutes' => $idleMinutes,

            /*
            | Existing trip metrics
            */
            'trip_distance_km' => $trip->distance_km,
            'trip_duration_seconds' => $trip->duration_seconds,
            'average_speed' => $trip->average_speed_km_per_hr,
            'max_speed' => $trip->max_speed_km_per_hr,

            'score_calculated_at' => now(),
        ]);

        $this->line(
            "Trip {$trip->id}: "
            ."Score {$score} "
            ."Risk {$riskScore} "
            ."Speed {$speedingScore} "
            ."Eco {$ecoScore} "
            ."Fatigue {$fatigueScore} "
            ."Distraction {$distractionScore} "
            ."Grade {$grade}"
        );
    }

    protected function counterDelta(
        ?int $previous,
        ?int $current
    ): int {
        $previous ??= 0;
        $current ??= 0;

        /*
        * If the tracker counter reset, don't treat the reset
        * as a huge number of events.
        */
        if ($current < $previous) {
            return $current;
        }

        return $current - $previous;
    }

    protected function pointTime($point): Carbon
    {
        return Carbon::parse($point->fix_time);
    }

    protected function intervalSeconds(
        $previous,
        $current
    ): int {
        if (
            ! $previous->fix_time ||
            ! $current->fix_time
        ) {
            return 0;
        }

        $seconds = Carbon::parse(
            $previous->fix_time
        )->diffInSeconds(
            Carbon::parse($current->fix_time)
        );

        /*
        * Don't let a telemetry gap of several hours turn
        * into several hours of idle/driving time.
        */
        return min(
            120,
            max(0, $seconds)
        );
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
