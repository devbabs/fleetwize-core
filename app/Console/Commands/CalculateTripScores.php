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
        $points = $trip->routePoints;

        $first = $points->first();
        $last = $points->last();

        $speedLimit = 120;
        $severeSpeedLimit = 140;

        $overspeedEvents = 0;
        $severeOverspeedEvents = 0;

        $isOverspeeding = false;
        $isSevereOverspeeding = false;

        foreach ($points as $point) {

            $speed = $point->speed_kmh ?? 0;

            $currentlyOverspeeding =
                $speed > $speedLimit;

            $currentlySevereOverspeeding =
                $speed > $severeSpeedLimit;

            if (
                $currentlyOverspeeding
                &&
                ! $isOverspeeding
            ) {
                $overspeedEvents++;
            }

            if (
                $currentlySevereOverspeeding
                &&
                ! $isSevereOverspeeding
            ) {
                $severeOverspeedEvents++;
            }

            $isOverspeeding = $currentlyOverspeeding;

            $isSevereOverspeeding =
                $currentlySevereOverspeeding;
        }

        $harshAcceleration = max(
            0,
            ($last->hard_acceleration_count ?? 0)
            -
            ($first->hard_acceleration_count ?? 0)
        );

        $harshBraking = max(
            0,
            ($last->hard_deceleration_count ?? 0)
            -
            ($first->hard_deceleration_count ?? 0)
        );

        $harshCornering = max(
            0,
            ($last->hard_cornering_count ?? 0)
            -
            ($first->hard_cornering_count ?? 0)
        );

        $idleSeconds = 0;

        for ($i = 1; $i < $points->count(); $i++) {

            $previous = $points[$i - 1];
            $current = $points[$i];

            if (
                $previous->ignition === true
                &&
                $previous->motion === false
            ) {

                if (
                    ! $previous->fix_time
                    ||
                    ! $current->fix_time
                ) {
                    continue;
                }

                $previousTime = Carbon::parse(
                    $previous->fix_time
                );

                $currentTime = Carbon::parse(
                    $current->fix_time
                );

                $seconds = min(
                    120,
                    max(
                        0,
                        $currentTime->diffInSeconds(
                            $previousTime
                        )
                    )
                );

                $idleSeconds += $seconds;
            }
        }

        $idleMinutes = (int) round(
            $idleSeconds / 60
        );

        $safetyScore = 100;

        $safetyScore -= ($overspeedEvents * 2);

        $safetyScore -= ($severeOverspeedEvents * 5);

        $safetyScore -= ($harshAcceleration * 3);

        $safetyScore -= ($harshBraking * 3);

        $safetyScore -= ($harshCornering * 2);

        $safetyScore = max(
            0,
            min(100, $safetyScore)
        );

        $efficiencyScore = 100;

        $efficiencyScore -= floor(
            $idleMinutes / 10
        );

        $efficiencyScore = max(
            0,
            min(100, $efficiencyScore)
        );

        $score = round(
            (
                ($safetyScore * 0.7)
                +
                ($efficiencyScore * 0.3)
            ),
            2
        );

        $grade = $this->grade($score);

        $trip->update([

            'score' => $score,

            'safety_score' => $safetyScore,

            'efficiency_score' => $efficiencyScore,

            'grade' => $grade,

            'overspeed_events' =>
                $overspeedEvents,

            'severe_overspeed_events' =>
                $severeOverspeedEvents,

            'harsh_acceleration_events' =>
                $harshAcceleration,

            'harsh_braking_events' =>
                $harshBraking,

            'harsh_cornering_events' =>
                $harshCornering,

            'idle_minutes' =>
                $idleMinutes,

            'trip_distance_km' =>
                $trip->distance_km,

            'trip_duration_seconds' =>
                $trip->duration_seconds,

            'average_speed' =>
                $trip->average_speed_km_per_hr,

            'max_speed' =>
                $trip->max_speed_km_per_hr,

            'score_calculated_at' =>
                now(),
        ]);

        $this->line(
            "Trip {$trip->id}: Score {$score} Grade {$grade}"
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
