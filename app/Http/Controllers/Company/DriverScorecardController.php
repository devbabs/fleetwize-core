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
                    $scorecard->companyUser?->user?->first_name
                    .' '.
                    $scorecard->companyUser?->user?->last_name,

                'period_type' =>
                    $scorecard->period_type,

                'period_start' =>
                    $scorecard->period_start?->toDateString(),

                'period_end' =>
                    $scorecard->period_end?->toDateString(),

                'trip_count' =>
                    $scorecard->trip_count,

                'score' =>
                    $scorecard->score,

                'safety_score' =>
                    $scorecard->safety_score,

                'efficiency_score' =>
                    $scorecard->efficiency_score,

                'grade' =>
                    $scorecard->grade,
            ]);

        return Inertia::render(
            'company/drivers/scorecards/index',
            [
                'scorecards' => $scorecards,
            ]
        );
    }

    public function show(Request $request, string $driver): Response
    {
        $company = $request->attributes->get('company');

        dd($driver);

        $driver = CompanyUser::findOrFail($driver);
        dd($driver);

        abort_unless(
            $driver->company_id === $company->id,
            404
        );

        $scorecards = DriverScorecard::query()
            ->where('company_user_id', $driver->id)
            ->latest('period_start')
            ->get();

        return Inertia::render(
            'company/drivers/scorecards/show',
            [
                'driver' => [
                    'id' => $driver->id,
                    'name' => $driver->user->first_name . ' ' . $driver->user->last_name,
                ],
                'scorecards' => $scorecards,
            ]
        );
    }
}
