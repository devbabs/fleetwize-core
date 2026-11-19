<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use App\Models\DriverScorecard;
use App\Models\VehicleTrip;
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
            ->get()
            ->map(fn (DriverScorecard $scorecard) => [
                'id' => $scorecard->id,
                'period_type' => $scorecard->period_type,
                'period_start' => $scorecard->period_start?->toDateString(),
                'period_end' => $scorecard->period_end?->toDateString(),
                'trip_count' => $scorecard->trip_count,

                // Overall
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

                // Trip summary metrics
                'distance_km' => $scorecard->distance_km,
                'duration_seconds' => $scorecard->duration_seconds,

                // Detailed breakdown
                'score_breakdown' => $scorecard->score_breakdown,
            ])
            ->values();

        return Inertia::render('company/drivers/scorecards/show', [
            'driver' => [
                'id' => $driverModel->id,
                'name' => trim(
                    ($driverModel->user?->first_name ?? '')
                    .' '.
                    ($driverModel->user?->last_name ?? '')
                ) ?: "Driver #{$driverModel->id}",
            ],
            'latest_scorecard' => $scorecards->first(),
            'scorecards' => $scorecards,
        ]);
    }
}
