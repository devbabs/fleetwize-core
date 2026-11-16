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

    protected $description = 'Generate daily, weekly and monthly driver scorecards across the 5 Pillars';

    public function handle(): int
    {
        $this->aggregateDaily();
        $this->aggregateWeekly();
        $this->aggregateMonthly();

        return self::SUCCESS;
    }

    protected function aggregateDaily(): void
    {
        // Yesterday (finalized daily scorecard)
        $yesterday = now()->subDay();
        $this->aggregatePeriod('daily', $yesterday->copy()->startOfDay(), $yesterday->copy()->endOfDay());

        // Today (live rolling daily scorecard)
        $today = now();
        $this->aggregatePeriod('daily', $today->copy()->startOfDay(), $today->copy()->endOfDay());
    }

    protected function aggregateWeekly(): void
    {
        // Last week (finalized)
        $lastWeek = now()->subWeek();
        $this->aggregatePeriod('weekly', $lastWeek->copy()->startOfWeek(), $lastWeek->copy()->endOfWeek());

        // Current week-to-date (live rolling)
        $thisWeek = now();
        $this->aggregatePeriod('weekly', $thisWeek->copy()->startOfWeek(), $thisWeek->copy()->endOfWeek());
    }

    protected function aggregateMonthly(): void
    {
        // Last month (finalized)
        $lastMonth = now()->subMonth();
        $this->aggregatePeriod('monthly', $lastMonth->copy()->startOfMonth(), $lastMonth->copy()->endOfMonth());

        // Current month-to-date (live rolling)
        $thisMonth = now();
        $this->aggregatePeriod('monthly', $thisMonth->copy()->startOfMonth(), $thisMonth->copy()->endOfMonth());
    }

    protected function aggregatePeriod(string $periodType, CarbonInterface $start, CarbonInterface $end): void
    {
        VehicleTrip::query()
            ->whereNotNull('company_user_id')
            ->whereNotNull('score_calculated_at')
            ->whereBetween('start_time', [$start, $end])
            ->select('company_user_id')
            ->distinct()
            ->get()
            ->each(function ($trip) use ($periodType, $start, $end) {
                $this->buildScorecard(
                    (int) $trip->company_user_id,
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

        $totalDistance = round($trips->sum(
            fn ($trip) => (float) ($trip->distance_km ?? 0)
        ), 2);

        /*
        |--------------------------------------------------------------------------
        | Five Pillar Scores (Distance-Weighted Averages)
        |--------------------------------------------------------------------------
        */
        $riskScore = $this->weightedAverage($trips, 'risk_score', $totalDistance);
        $speedingScore = $this->weightedAverage($trips, 'speeding_score', $totalDistance);
        $ecoScore = $this->weightedAverage($trips, 'eco_score', $totalDistance);
        $fatigueScore = $this->weightedAverage($trips, 'fatigue_score', $totalDistance);
        $distractionScore = $this->weightedAverage($trips, 'distraction_score', $totalDistance);

        /*
        |--------------------------------------------------------------------------
        | Weighted Overall Score (Risk 25%, Speeding 25%, Eco 20%, Fatigue 15%, Distraction 15%)
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

        $safetyScore = round(($riskScore + $speedingScore) / 2, 2);
        $efficiencyScore = round(($ecoScore + $distractionScore) / 2, 2);

        /*
        |--------------------------------------------------------------------------
        | Aggregate Supporting Metrics
        |--------------------------------------------------------------------------
        */
        $durationSeconds = (int) $trips->sum(fn ($trip) => (int) ($trip->duration_seconds ?? 0));
        $overspeedEvents = (int) $trips->sum(fn ($trip) => (int) ($trip->overspeed_events ?? 0));
        $severeOverspeedEvents = (int) $trips->sum(fn ($trip) => (int) ($trip->severe_overspeed_events ?? 0));
        $overspeedDurationSeconds = (int) $trips->sum(fn ($trip) => (int) ($trip->overspeed_duration_seconds ?? 0));
        $harshAccelerationEvents = (int) $trips->sum(fn ($trip) => (int) ($trip->harsh_acceleration_events ?? 0));
        $harshBrakingEvents = (int) $trips->sum(fn ($trip) => (int) ($trip->harsh_braking_events ?? 0));
        $harshCorneringEvents = (int) $trips->sum(fn ($trip) => (int) ($trip->harsh_cornering_events ?? 0));
        $highEngineLoadEvents = (int) $trips->sum(fn ($trip) => (int) ($trip->high_engine_load_events ?? 0));
        $fatigueEvents = (int) $trips->sum(fn ($trip) => (int) ($trip->fatigue_events ?? 0));

        // Longest single continuous driving stint in the period (not sum of all trips)
        $continuousDrivingSeconds = (int) $trips->max(
            fn ($trip) => (int) ($trip->continuous_driving_seconds ?? $trip->duration_seconds ?? 0)
        );

        $idleMinutes = (int) $trips->sum(fn ($trip) => (int) ($trip->idle_minutes ?? 0));

        // Normalized rates per 100 km & idle ratio for UI coaching insights
        $distanceFactor = $totalDistance > 0 ? (100.0 / $totalDistance) : 0;
        $totalDrivingMinutes = max(1, round($durationSeconds / 60));
        $idleRatioPercent = round(min(100, ($idleMinutes / $totalDrivingMinutes) * 100), 1);

        $scoreBreakdown = [
            'risk' => [
                'score' => $riskScore,
                'weight_percent' => 25,
                'harsh_braking_events' => $harshBrakingEvents,
                'harsh_cornering_events' => $harshCorneringEvents,
                'events_per_100km' => round(($harshBrakingEvents + $harshCorneringEvents) * $distanceFactor, 1),
            ],
            'speeding' => [
                'score' => $speedingScore,
                'weight_percent' => 25,
                'overspeed_events' => $overspeedEvents,
                'severe_overspeed_events' => $severeOverspeedEvents,
                'overspeed_duration_seconds' => $overspeedDurationSeconds,
                'events_per_100km' => round(($overspeedEvents + $severeOverspeedEvents) * $distanceFactor, 1),
            ],
            'eco' => [
                'score' => $ecoScore,
                'weight_percent' => 20,
                'harsh_acceleration_events' => $harshAccelerationEvents,
                'high_engine_load_events' => $highEngineLoadEvents,
                'estimated_idle_fuel_liters' => round(($idleMinutes / 60.0) * 1.5, 2),
            ],
            'fatigue' => [
                'score' => $fatigueScore,
                'weight_percent' => 15,
                'fatigue_events' => $fatigueEvents,
                'continuous_driving_seconds' => $continuousDrivingSeconds,
                'max_continuous_hours' => round($continuousDrivingSeconds / 3600.0, 1),
            ],
            'distraction' => [
                'score' => $distractionScore,
                'weight_percent' => 15,
                'idle_minutes' => $idleMinutes,
                'idle_ratio_percent' => $idleRatioPercent,
            ],
        ];

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
                'risk_score' => $riskScore,
                'speeding_score' => $speedingScore,
                'eco_score' => $ecoScore,
                'fatigue_score' => $fatigueScore,
                'distraction_score' => $distractionScore,
                'safety_score' => $safetyScore,
                'efficiency_score' => $efficiencyScore,
                'distance_km' => $totalDistance,
                'duration_seconds' => $durationSeconds,
                'overspeed_events' => $overspeedEvents,
                'severe_overspeed_events' => $severeOverspeedEvents,
                'overspeed_duration_seconds' => $overspeedDurationSeconds,
                'harsh_acceleration_events' => $harshAccelerationEvents,
                'harsh_braking_events' => $harshBrakingEvents,
                'harsh_cornering_events' => $harshCorneringEvents,
                'high_engine_load_events' => $highEngineLoadEvents,
                'fatigue_events' => $fatigueEvents,
                'continuous_driving_seconds' => $continuousDrivingSeconds,
                'idle_minutes' => $idleMinutes,
                'score_breakdown' => $scoreBreakdown,
                'grade' => $this->grade($score),
            ]
        );
    }

    protected function weightedAverage($trips, string $scoreColumn, float $totalDistance): float
    {
        if ($totalDistance > 0) {
            $weightedTotal = $trips->sum(function ($trip) use ($scoreColumn) {
                $score = $trip->{$scoreColumn};
                if ($score === null) {
                    return 0;
                }

                return (float) $score * (float) ($trip->distance_km ?? 0);
            });

            $distanceForScoredTrips = $trips
                ->filter(fn ($trip) => $trip->{$scoreColumn} !== null)
                ->sum(fn ($trip) => (float) ($trip->distance_km ?? 0));

            if ($distanceForScoredTrips > 0) {
                return round($weightedTotal / $distanceForScoredTrips, 2);
            }
        }

        $scores = $trips
            ->pluck($scoreColumn)
            ->filter(fn ($score) => $score !== null);

        if ($scores->isEmpty()) {
            return 100.0;
        }

        return round((float) $scores->avg(), 2);
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
