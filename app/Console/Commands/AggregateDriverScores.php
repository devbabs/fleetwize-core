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
    protected $signature = 'tracker:aggregate-driver-scores
                            {--days=2 : Number of past days to aggregate for daily scorecards}';

    protected $description = 'Generate daily, weekly and monthly driver scorecards across the 5 core driving behaviours';

    public function handle(): int
    {
        $now = now();
        $days = max(2, (int) $this->option('days'));

        // Daily: last N days including today
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i);
            $this->aggregatePeriod('daily', $day->copy()->startOfDay(), $day->copy()->endOfDay());
        }

        // Weekly: last week + current week
        $this->aggregatePeriod('weekly', $now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek());
        $this->aggregatePeriod('weekly', $now->copy()->startOfWeek(), $now->copy()->endOfWeek());

        // Monthly: last month + current month
        $this->aggregatePeriod('monthly', $now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth());
        $this->aggregatePeriod('monthly', $now->copy()->startOfMonth(), $now->copy()->endOfMonth());

        return self::SUCCESS;
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
                $this->buildScorecard((int) $trip->company_user_id, $periodType, $start, $end);
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

        $totalDistance = round($trips->sum(fn ($trip) => (float) ($trip->distance_km ?? 0)), 2);
        $durationSeconds = (int) $trips->sum(fn ($trip) => (int) ($trip->duration_seconds ?? 0));

        $harshBrakingEvents = (int) $trips->sum(fn ($trip) => (int) ($trip->harsh_braking_events ?? 0));
        $harshAccelerationEvents = (int) $trips->sum(fn ($trip) => (int) ($trip->harsh_acceleration_events ?? 0));
        $overspeedEvents = (int) $trips->sum(fn ($trip) => (int) ($trip->overspeed_events ?? 0));
        $harshCorneringEvents = (int) $trips->sum(fn ($trip) => (int) ($trip->harsh_cornering_events ?? 0));
        $idleMinutes = (int) $trips->sum(fn ($trip) => (int) ($trip->idle_minutes ?? 0));

        $harshBrakingScore = $this->weightedAverage($trips, 'harsh_braking_score', $totalDistance);
        $harshAccelerationScore = $this->weightedAverage($trips, 'harsh_acceleration_score', $totalDistance);
        $speedingScore = $this->weightedAverage($trips, 'speeding_score', $totalDistance);
        $hardCorneringScore = $this->weightedAverage($trips, 'hard_cornering_score', $totalDistance);
        $idlingScore = $this->weightedAverage($trips, 'idling_score', $totalDistance);

        $totalDistance = round($trips->sum(fn ($trip) => (float) ($trip->distance_km ?? 0)), 2);
        $durationSeconds = (int) $trips->sum(fn ($trip) => (int) ($trip->duration_seconds ?? 0));

        // Total fuel burned (Liters) across all trips in this period
        $totalFuelConsumed = round((float) $trips->sum(
            fn ($trip) => (float) ($trip->fuel_consumed ?? 0)
        ), 2);

        // Fuel efficiency (km/L) when both distance and fuel are > 0
        $fuelEfficiencyKmPerL = ($totalFuelConsumed > 0 && $totalDistance > 0)
            ? round($totalDistance / $totalFuelConsumed, 2)
            : null;

        $score = round(
            ($harshBrakingScore * 0.20)
            + ($harshAccelerationScore * 0.20)
            + ($speedingScore * 0.20)
            + ($hardCorneringScore * 0.20)
            + ($idlingScore * 0.20),
            2
        );

        $scoreBreakdown = [
            'harsh_braking' => [
                'score' => $harshBrakingScore,
                'events' => $harshBrakingEvents,
            ],
            'harsh_acceleration' => [
                'score' => $harshAccelerationScore,
                'events' => $harshAccelerationEvents,
            ],
            'speeding' => [
                'score' => $speedingScore,
                'events' => $overspeedEvents,
            ],
            'hard_cornering' => [
                'score' => $hardCorneringScore,
                'events' => $harshCorneringEvents,
            ],
            'idling' => [
                'score' => $idlingScore,
                'idle_minutes' => $idleMinutes,
            ],
            'fuel' => [
                'consumed_liters' => $totalFuelConsumed,
                'km_per_liter' => $fuelEfficiencyKmPerL,
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
                'grade' => $this->grade($score),
                'harsh_braking_score' => $harshBrakingScore,
                'harsh_acceleration_score' => $harshAccelerationScore,
                'speeding_score' => $speedingScore,
                'hard_cornering_score' => $hardCorneringScore,
                'idling_score' => $idlingScore,
                'distance_km' => $totalDistance,
                'fuel_consumed' => $totalFuelConsumed,
                'duration_seconds' => $durationSeconds,
                'harsh_braking_events' => $harshBrakingEvents,
                'harsh_acceleration_events' => $harshAccelerationEvents,
                'overspeed_events' => $overspeedEvents,
                'harsh_cornering_events' => $harshCorneringEvents,
                'idle_minutes' => $idleMinutes,
                'score_breakdown' => $scoreBreakdown,
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
