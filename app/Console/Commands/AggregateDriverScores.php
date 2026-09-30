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

    protected function buildScorecard(int $companyUserId, string $periodType, CarbonInterface $start, CarbonInterface $end): void
    {

        $trips = VehicleTrip::query()
            ->where(
                'company_user_id',
                $companyUserId
            )
            ->whereBetween(
                'start_time',
                [$start, $end]
            )
            ->get();

        if ($trips->isEmpty()) {
            return;
        }

        $totalDistance = $trips->sum('distance_km');

        $score = $totalDistance > 0
            ? round(
                $trips->sum(
                    fn ($trip) =>
                        $trip->score * $trip->distance_km
                ) / $totalDistance,
                2
            )
            : 0;

        $safetyScore = $totalDistance > 0
            ? round(
                $trips->sum(
                    fn ($trip) =>
                        $trip->safety_score * $trip->distance_km
                ) / $totalDistance,
                2
            )
            : 0;

        $efficiencyScore = $totalDistance > 0
            ? round(
                $trips->sum(
                    fn ($trip) =>
                        $trip->efficiency_score * $trip->distance_km
                ) / $totalDistance,
                2
            )
            : 0;

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

                'safety_score' => $safetyScore,

                'efficiency_score' => $efficiencyScore,

                'distance_km' =>
                    $trips->sum('distance_km'),

                'duration_seconds' =>
                    $trips->sum('duration_seconds'),

                'overspeed_events' =>
                    $trips->sum('overspeed_events'),

                'severe_overspeed_events' =>
                    $trips->sum('severe_overspeed_events'),

                'harsh_acceleration_events' =>
                    $trips->sum('harsh_acceleration_events'),

                'harsh_braking_events' =>
                    $trips->sum('harsh_braking_events'),

                'harsh_cornering_events' =>
                    $trips->sum('harsh_cornering_events'),

                'idle_minutes' =>
                    $trips->sum('idle_minutes'),

                'grade' =>
                    $this->grade($score),
            ]
        );
    }
}
