<?php

namespace App\Console\Commands;

use App\Models\DriverScorecard;
use App\Models\VehicleTrip;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Carbon\CarbonInterface;
class AggregateDriverScores extends Command
{
    protected $signature = 'tracker:aggregate-driver-scores';

    protected $description = 'Generate daily, weekly and monthly driver scorecards';
    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $this->aggregateDaily();

        $this->aggregateWeekly();

        $this->aggregateMonthly();

        return self::SUCCESS;
    }

    protected function aggregateDaily(): void
    {
        $date = now()->subDay()->toDateString();

        $this->aggregatePeriod(
            'daily',
            Carbon::parse($date)->startOfDay(),
            Carbon::parse($date)->endOfDay()
        );
    }

    protected function aggregateWeekly(): void
    {
        $start = now()
            ->subWeek()
            ->startOfWeek();

        $end = now()
            ->subWeek()
            ->endOfWeek();

        $this->aggregatePeriod(
            'weekly',
            $start,
            $end
        );
    }

    protected function aggregateMonthly(): void
    {
        $start = now()
            ->subMonth()
            ->startOfMonth();

        $end = now()
            ->subMonth()
            ->endOfMonth();

        $this->aggregatePeriod(
            'monthly',
            $start,
            $end
        );
    }

    protected function aggregatePeriod(string $periodType, CarbonInterface $start, CarbonInterface $end): void 
    {

        VehicleTrip::query()
            ->whereNotNull('company_user_id')
            ->whereNotNull('score_calculated_at')
            ->whereBetween(
                'start_time',
                [$start, $end]
            )
            ->select('company_user_id')
            ->distinct()
            ->get()
            ->each(function ($trip) use (
                $periodType,
                $start,
                $end
            ) {

                $this->buildScorecard(
                    $trip->company_user_id,
                    $periodType,
                    $start,
                    $end
                );
            });
    }

    protected function buildScorecard(
        int $companyUserId,
        string $periodType,
        CarbonInterface $start,
        CarbonInterface $end
    ): void {
        $trips = VehicleTrip::query()
            ->where('company_user_id', $companyUserId)
            ->whereNotNull('score_calculated_at')
            ->whereBetween('start_time', [$start, $end])
            ->get();

        if ($trips->isEmpty()) {
            return;
        }

        $totalDistance = $trips->sum(
            fn ($trip) => (float) ($trip->distance_km ?? 0)
        );

        /*
        |--------------------------------------------------------------------------
        | Five pillar scores
        |--------------------------------------------------------------------------
        |
        | Distance-weighted averages are preferable because a 2 km trip
        | shouldn't have the same influence as a 200 km trip.
        |
        */

        $riskScore = $this->weightedAverage(
            $trips,
            'risk_score',
            $totalDistance
        );

        $speedingScore = $this->weightedAverage(
            $trips,
            'speeding_score',
            $totalDistance
        );

        $ecoScore = $this->weightedAverage(
            $trips,
            'eco_score',
            $totalDistance
        );

        $fatigueScore = $this->weightedAverage(
            $trips,
            'fatigue_score',
            $totalDistance
        );

        $distractionScore = $this->weightedAverage(
            $trips,
            'distraction_score',
            $totalDistance
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

        /*
        |--------------------------------------------------------------------------
        | Backward compatibility
        |--------------------------------------------------------------------------
        |
        | Keep these until the frontend/API has completely moved to the
        | five-pillar model.
        |
        */

        $safetyScore = round(
            ($riskScore + $speedingScore) / 2,
            2
        );

        $efficiencyScore = round(
            ($ecoScore + $distractionScore) / 2,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Aggregate metrics
        |--------------------------------------------------------------------------
        */

        $distanceKm = $trips->sum(
            fn ($trip) => (float) ($trip->distance_km ?? 0)
        );

        $durationSeconds = $trips->sum(
            fn ($trip) => (int) ($trip->duration_seconds ?? 0)
        );

        $overspeedEvents = $trips->sum(
            fn ($trip) => (int) ($trip->overspeed_events ?? 0)
        );

        $severeOverspeedEvents = $trips->sum(
            fn ($trip) => (int) ($trip->severe_overspeed_events ?? 0)
        );

        $overspeedDurationSeconds = $trips->sum(
            fn ($trip) => (int) ($trip->overspeed_duration_seconds ?? 0)
        );

        $harshAccelerationEvents = $trips->sum(
            fn ($trip) => (int) ($trip->harsh_acceleration_events ?? 0)
        );

        $harshBrakingEvents = $trips->sum(
            fn ($trip) => (int) ($trip->harsh_braking_events ?? 0)
        );

        $harshCorneringEvents = $trips->sum(
            fn ($trip) => (int) ($trip->harsh_cornering_events ?? 0)
        );

        $highEngineLoadEvents = $trips->sum(
            fn ($trip) => (int) ($trip->high_engine_load_events ?? 0)
        );

        $fatigueEvents = $trips->sum(
            fn ($trip) => (int) ($trip->fatigue_events ?? 0)
        );

        $continuousDrivingSeconds = $trips->sum(
            fn ($trip) => (int) ($trip->continuous_driving_seconds ?? 0)
        );

        $idleMinutes = $trips->sum(
            fn ($trip) => (int) ($trip->idle_minutes ?? 0)
        );

        /*
        |--------------------------------------------------------------------------
        | Score breakdown
        |--------------------------------------------------------------------------
        |
        | This makes the scorecard easier to explain in the UI later.
        |
        */

        $scoreBreakdown = [
            'risk' => [
                'score' => $riskScore,
                'harsh_braking_events' => $harshBrakingEvents,
                'harsh_cornering_events' => $harshCorneringEvents,
            ],

            'speeding' => [
                'score' => $speedingScore,
                'overspeed_events' => $overspeedEvents,
                'severe_overspeed_events' => $severeOverspeedEvents,
                'overspeed_duration_seconds' =>
                    $overspeedDurationSeconds,
            ],

            'eco' => [
                'score' => $ecoScore,
                'harsh_acceleration_events' =>
                    $harshAccelerationEvents,
                'high_engine_load_events' =>
                    $highEngineLoadEvents,
            ],

            'fatigue' => [
                'score' => $fatigueScore,
                'fatigue_events' => $fatigueEvents,
                'continuous_driving_seconds' =>
                    $continuousDrivingSeconds,
            ],

            'distraction' => [
                'score' => $distractionScore,
                'idle_minutes' => $idleMinutes,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Save scorecard
        |--------------------------------------------------------------------------
        */

        DriverScorecard::updateOrCreate(
            [
                'company_user_id' => $companyUserId,
                'period_type' => $periodType,
                'period_start' => $start->toDateString(),
            ],
            [
                'period_end' => $end->toDateString(),

                'trip_count' => $trips->count(),

                'score' => $score,

                /*
                | Five pillars
                */
                'risk_score' => $riskScore,
                'speeding_score' => $speedingScore,
                'eco_score' => $ecoScore,
                'fatigue_score' => $fatigueScore,
                'distraction_score' => $distractionScore,

                /*
                | Backward compatibility
                */
                'safety_score' => $safetyScore,
                'efficiency_score' => $efficiencyScore,

                /*
                | Trip metrics
                */
                'distance_km' => $distanceKm,
                'duration_seconds' => $durationSeconds,

                'overspeed_events' => $overspeedEvents,
                'severe_overspeed_events' =>
                    $severeOverspeedEvents,

                'overspeed_duration_seconds' =>
                    $overspeedDurationSeconds,

                'harsh_acceleration_events' =>
                    $harshAccelerationEvents,

                'harsh_braking_events' =>
                    $harshBrakingEvents,

                'harsh_cornering_events' =>
                    $harshCorneringEvents,

                'high_engine_load_events' =>
                    $highEngineLoadEvents,

                'fatigue_events' =>
                    $fatigueEvents,

                'continuous_driving_seconds' =>
                    $continuousDrivingSeconds,

                'idle_minutes' => $idleMinutes,

                /*
                | Detailed explanation of the score
                */
                'score_breakdown' => $scoreBreakdown,

                'grade' => $this->grade($score),
            ]
        );
    }

    protected function weightedAverage(
        $trips,
        string $scoreColumn,
        float $totalDistance
    ): float {
        /*
        * Prefer distance-weighted scoring when there is
        * meaningful distance data.
        */
        if ($totalDistance > 0) {

            $weightedTotal = $trips->sum(
                function ($trip) use ($scoreColumn) {

                    $score = $trip->{$scoreColumn};

                    if ($score === null) {
                        return 0;
                    }

                    return
                        (float) $score
                        *
                        (float) ($trip->distance_km ?? 0);
                }
            );

            $distanceForScoredTrips = $trips
                ->filter(
                    fn ($trip) =>
                        $trip->{$scoreColumn} !== null
                )
                ->sum(
                    fn ($trip) =>
                        (float) ($trip->distance_km ?? 0)
                );

            if ($distanceForScoredTrips > 0) {
                return round(
                    $weightedTotal / $distanceForScoredTrips,
                    2
                );
            }
        }

        /*
        * Fallback for trips with no distance.
        */
        $scores = $trips
            ->pluck($scoreColumn)
            ->filter(fn ($score) => $score !== null);

        if ($scores->isEmpty()) {
            return 100;
        }

        return round(
            $scores->avg(),
            2
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
