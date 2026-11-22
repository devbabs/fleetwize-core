<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use App\Models\DriverScorecard;
use App\Models\VehicleTrip;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DriverScorecardController extends Controller
{
    public function index(Request $request): Response
    {
        $company = $request->attributes->get('company');

        $scorecards = DriverScorecard::query()
            ->with(['companyUser.user'])
            ->whereHas(
                'companyUser',
                fn ($query) => $query->where('company_id', $company->id)
            )
            ->latest('period_start')
            ->paginate(20)
            ->through(fn (DriverScorecard $scorecard) => [
                'id' => $scorecard->id,
                'driver_id' => $scorecard->company_user_id,
                'driver_name' => trim(
                    ($scorecard->companyUser?->user?->first_name ?? '')
                    .' '.
                    ($scorecard->companyUser?->user?->last_name ?? '')
                ) ?: "Driver #{$scorecard->company_user_id}",
                'period_type' => $scorecard->period_type,
                'period_start' => $scorecard->period_start?->toDateString(),
                'period_end' => $scorecard->period_end?->toDateString(),
                'trip_count' => $scorecard->trip_count,

                // Overall score
                'score' => $scorecard->score,
                'grade' => $scorecard->grade,

                // 5 core behaviour scores (0 - 100)
                'harsh_braking_score' => $scorecard->harsh_braking_score,
                'harsh_acceleration_score' => $scorecard->harsh_acceleration_score,
                'speeding_score' => $scorecard->speeding_score,
                'hard_cornering_score' => $scorecard->hard_cornering_score,
                'idling_score' => $scorecard->idling_score,

                // 5 core behaviour counts / minutes
                'harsh_braking_events' => $scorecard->harsh_braking_events,
                'harsh_acceleration_events' => $scorecard->harsh_acceleration_events,
                'overspeed_events' => $scorecard->overspeed_events,
                'harsh_cornering_events' => $scorecard->harsh_cornering_events,
                'idle_minutes' => $scorecard->idle_minutes,
            ]);

        return Inertia::render('company/drivers/scorecards/index', [
            'scorecards' => $scorecards,
        ]);
    }

    public function show(Request $request, string $company_slug, string $driver): Response
    {
        $company = $request->attributes->get('company');

        $driverModel = CompanyUser::with('user')->findOrFail($driver);

        abort_unless(
            (int) $driverModel->company_id === (int) $company->id,
            404
        );

        $scorecards = DriverScorecard::query()
            ->where('company_user_id', $driverModel->id)
            ->latest('period_start')
            ->get();

        $selectedId = $request->integer('scorecard');
        $selectedModel = $selectedId
            ? ($scorecards->firstWhere('id', $selectedId) ?? $scorecards->first())
            : $scorecards->first();

        $trips = collect();

        if ($selectedModel && $selectedModel->period_start && $selectedModel->period_end) {
            $periodStart = Carbon::parse($selectedModel->period_start)->startOfDay();
            $periodEnd = Carbon::parse($selectedModel->period_end)->endOfDay();

            $trips = VehicleTrip::query()
                ->with('vehicle:id,name,license_plate')
                ->where('company_user_id', $driverModel->id)
                ->whereNotNull('score_calculated_at')
                ->whereBetween('start_time', [$periodStart, $periodEnd])
                ->orderByDesc('start_time')
                ->get()
                ->map(fn (VehicleTrip $trip) => [
                    'id' => $trip->id,
                    'vehicle_name' => $trip->vehicle?->name ?? 'Vehicle',
                    'license_plate' => $trip->vehicle?->license_plate,
                    'start_time' => $trip->start_time?->toIso8601String(),
                    'end_time' => $trip->end_time?->toIso8601String(),
                    'distance_km' => $trip->distance_km !== null ? (float) $trip->distance_km : 0,
                    'fuel_consumed' => $trip->fuel_consumed !== null ? (float) $trip->fuel_consumed : null,
                    'max_speed' => $trip->max_speed_km_per_hr !== null ? (float) $trip->max_speed_km_per_hr : null,
                    'score' => $trip->score !== null ? (float) $trip->score : 0,
                    'grade' => $trip->grade ?? 'A',
                    'harsh_braking_score' => $trip->harsh_braking_score !== null ? (float) $trip->harsh_braking_score : 100,
                    'harsh_acceleration_score' => $trip->harsh_acceleration_score !== null ? (float) $trip->harsh_acceleration_score : 100,
                    'speeding_score' => $trip->speeding_score !== null ? (float) $trip->speeding_score : 100,
                    'hard_cornering_score' => $trip->hard_cornering_score !== null ? (float) $trip->hard_cornering_score : 100,
                    'idling_score' => $trip->idling_score !== null ? (float) $trip->idling_score : 100,
                    'harsh_braking_events' => (int) ($trip->harsh_braking_events ?? 0),
                    'harsh_acceleration_events' => (int) ($trip->harsh_acceleration_events ?? 0),
                    'overspeed_events' => (int) ($trip->overspeed_events ?? 0),
                    'harsh_cornering_events' => (int) ($trip->harsh_cornering_events ?? 0),
                    'idle_minutes' => (int) ($trip->idle_minutes ?? 0),
                ])
                ->values();
        }

        $formatScorecard = fn (DriverScorecard $scorecard) => [
            'id' => $scorecard->id,
            'period_type' => $scorecard->period_type,
            'period_start' => $scorecard->period_start?->toDateString(),
            'period_end' => $scorecard->period_end?->toDateString(),
            'trip_count' => $scorecard->trip_count,
            'score' => $scorecard->score,
            'grade' => $scorecard->grade,
            'harsh_braking_score' => $scorecard->harsh_braking_score,
            'harsh_acceleration_score' => $scorecard->harsh_acceleration_score,
            'speeding_score' => $scorecard->speeding_score,
            'hard_cornering_score' => $scorecard->hard_cornering_score,
            'idling_score' => $scorecard->idling_score,
            'harsh_braking_events' => $scorecard->harsh_braking_events,
            'harsh_acceleration_events' => $scorecard->harsh_acceleration_events,
            'overspeed_events' => $scorecard->overspeed_events,
            'harsh_cornering_events' => $scorecard->harsh_cornering_events,
            'idle_minutes' => $scorecard->idle_minutes,
            'distance_km' => $scorecard->distance_km,
            'fuel_consumed' => $scorecard->fuel_consumed ?? null,
            'duration_seconds' => $scorecard->duration_seconds,
            'score_breakdown' => $scorecard->score_breakdown,
        ];

        return Inertia::render('company/drivers/scorecards/show', [
            'driver' => [
                'id' => $driverModel->id,
                'name' => trim(
                    ($driverModel->user?->first_name ?? '')
                    .' '.
                    ($driverModel->user?->last_name ?? '')
                ) ?: "Driver #{$driverModel->id}",
            ],
            'selected_scorecard' => $selectedModel ? $formatScorecard($selectedModel) : null,
            'trips' => $trips,
            'scorecards' => $scorecards->map($formatScorecard)->values(),
        ]);
    }
}
