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
    //
    public function index(Request $request): Response
    {
        $company = $request->attributes->get('company');

        $scorecards = DriverScorecard::query()
            ->with([
                'companyUser.user',
            ])
            ->whereHas(
                'companyUser',
                fn ($query) =>
                    $query->where(
                        'company_id',
                        $company->id
                    )
            )
            ->latest('period_start')
            ->paginate(20)
            ->through(fn ($scorecard) => [
                'id' => $scorecard->id,

                'driver_id' =>
                    $scorecard->company_user_id,

                'driver_name' =>
                    trim(
                        ($scorecard->companyUser?->user?->first_name ?? '')
                        .' '.
                        ($scorecard->companyUser?->user?->last_name ?? '')
                    ),

                'period_type' =>
                    $scorecard->period_type,

                'period_start' =>
                    $scorecard->period_start?->toDateString(),

                'period_end' =>
                    $scorecard->period_end?->toDateString(),

                'trip_count' =>
                    $scorecard->trip_count,

                /*
                |--------------------------------------------------------------------------
                | Overall score
                |--------------------------------------------------------------------------
                */

                'score' =>
                    $scorecard->score,

                'grade' =>
                    $scorecard->grade,

                /*
                |--------------------------------------------------------------------------
                | Five pillars
                |--------------------------------------------------------------------------
                */

                'risk_score' =>
                    $scorecard->risk_score,

                'speeding_score' =>
                    $scorecard->speeding_score,

                'eco_score' =>
                    $scorecard->eco_score,

                'fatigue_score' =>
                    $scorecard->fatigue_score,

                'distraction_score' =>
                    $scorecard->distraction_score,
            ]);

        return Inertia::render(
            'company/drivers/scorecards/index',
            [
                'scorecards' => $scorecards,
            ]
        );
    }

    public function show(Request $request, string $company_slug, string $driver): Response 
    {
        $company = $request->attributes->get('company');

        $driver = CompanyUser::with('user')
            ->findOrFail($driver);

        abort_unless(
            $driver->company_id === $company->id,
            404
        );

        $scorecards = DriverScorecard::query()
            ->where('company_user_id', $driver->id)
            ->latest('period_start')
            ->get()
            ->map(fn ($scorecard) => [
                'id' => $scorecard->id,

                'period_type' =>
                    $scorecard->period_type,

                'period_start' =>
                    $scorecard->period_start?->toDateString(),

                'period_end' =>
                    $scorecard->period_end?->toDateString(),

                'trip_count' =>
                    $scorecard->trip_count,

                /*
                |--------------------------------------------------------------------------
                | Overall
                |--------------------------------------------------------------------------
                */

                'score' =>
                    $scorecard->score,

                'grade' =>
                    $scorecard->grade,

                /*
                |--------------------------------------------------------------------------
                | Five pillars
                |--------------------------------------------------------------------------
                */

                'risk_score' =>
                    $scorecard->risk_score,

                'speeding_score' =>
                    $scorecard->speeding_score,

                'eco_score' =>
                    $scorecard->eco_score,

                'fatigue_score' =>
                    $scorecard->fatigue_score,

                'distraction_score' =>
                    $scorecard->distraction_score,

                /*
                |--------------------------------------------------------------------------
                | Supporting metrics
                |--------------------------------------------------------------------------
                */

                'distance_km' =>
                    $scorecard->distance_km,

                'duration_seconds' =>
                    $scorecard->duration_seconds,

                'overspeed_events' =>
                    $scorecard->overspeed_events,

                'severe_overspeed_events' =>
                    $scorecard->severe_overspeed_events,

                'overspeed_duration_seconds' =>
                    $scorecard->overspeed_duration_seconds,

                'harsh_acceleration_events' =>
                    $scorecard->harsh_acceleration_events,

                'harsh_braking_events' =>
                    $scorecard->harsh_braking_events,

                'harsh_cornering_events' =>
                    $scorecard->harsh_cornering_events,

                'high_engine_load_events' =>
                    $scorecard->high_engine_load_events,

                'fatigue_events' =>
                    $scorecard->fatigue_events,

                'continuous_driving_seconds' =>
                    $scorecard->continuous_driving_seconds,

                'idle_minutes' =>
                    $scorecard->idle_minutes,

                /*
                |--------------------------------------------------------------------------
                | Detailed breakdown
                |--------------------------------------------------------------------------
                */

                'score_breakdown' =>
                    $scorecard->score_breakdown,
            ]);

        $latestScorecard = $scorecards->first();

        return Inertia::render(
            'company/drivers/scorecards/show',
            [
                'driver' => [
                    'id' => $driver->id,

                    'name' =>
                        trim(
                            ($driver->user?->first_name ?? '')
                            .' '.
                            ($driver->user?->last_name ?? '')
                        ),
                ],

                'latest_scorecard' => $latestScorecard,

                'scorecards' => $scorecards,
            ]
        );
    }
}
