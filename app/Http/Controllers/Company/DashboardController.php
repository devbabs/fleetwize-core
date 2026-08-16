<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Models\VehicleFault;
use App\Models\VehicleServiceEntry;
use App\Models\VehicleTrackerState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use ResolvesCompany;

    public function index(Request $request): Response
    {
        $company = $this->currentCompany($request);

        $vehicleIds = $company->vehicles()->pluck('id');

        $onlineCount = $vehicleIds->isEmpty() ? 0 : VehicleTrackerState::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->where('reported_at', '>', now()->subMinutes(5))
            ->count();

        $movingCount = $vehicleIds->isEmpty() ? 0 : VehicleTrackerState::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->where('reported_at', '>', now()->subMinutes(5))
            ->where('speed', '>', 5)
            ->count();

        $openFaultsCount = VehicleFault::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->whereNull('cleared_at')
            ->count();

        $faultsTodayCount = VehicleFault::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->whereDate('log_time', today())
            ->count();

        $upcomingServiceCount = VehicleServiceEntry::query()
            ->where('company_id', $company->id)
            ->whereBetween('starts_at', [now(), now()->addDays(30)])
            ->count();

        $categoryBreakdown = DB::table('vehicles')
            ->where('company_id', $company->id)
            ->whereNull('deleted_at')
            ->selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => ['category' => $row->category, 'count' => (int) $row->count]);

        $faultsByDay = VehicleFault::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->where('log_time', '>=', now()->subDays(6)->startOfDay())
            ->whereNotNull('log_time')
            ->get(['log_time'])
            ->groupBy(fn (VehicleFault $fault) => $fault->log_time->format('Y-m-d'))
            ->map->count();

        $faultsTrend = collect(range(6, 0))->map(function (int $daysAgo) use ($faultsByDay) {
            $date = now()->subDays($daysAgo);

            return [
                'date' => $date->format('D'),
                'count' => $faultsByDay->get($date->format('Y-m-d'), 0),
            ];
        });

        $recentFaults = VehicleFault::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->whereNull('cleared_at')
            ->with('vehicle:id,name,license_plate,make,model')
            ->latest('log_time')
            ->limit(6)
            ->get()
            ->map(fn (VehicleFault $fault) => [
                'id' => $fault->id,
                'vehicle' => $fault->vehicle->license_plate ?? $fault->vehicle->name,
                'code' => $fault->obd_code,
                'meaning' => $fault->meaning,
                'severity' => $fault->severity,
                'logTime' => $fault->log_time?->toIso8601String(),
            ]);

        return Inertia::render('company/overview', [
            'stats' => [
                'totalVehicles' => $vehicleIds->count(),
                'onlineNow' => $onlineCount,
                'movingNow' => $movingCount,
                'openFaults' => $openFaultsCount,
                'faultsToday' => $faultsTodayCount,
                'upcomingService' => $upcomingServiceCount,
            ],
            'recentFaults' => $recentFaults,
            'charts' => [
                'fleetStatus' => [
                    ['name' => 'Moving', 'value' => $movingCount],
                    ['name' => 'Idle', 'value' => max($onlineCount - $movingCount, 0)],
                    ['name' => 'Offline', 'value' => max($vehicleIds->count() - $onlineCount, 0)],
                ],
                'categoryBreakdown' => $categoryBreakdown,
                'faultsTrend' => $faultsTrend,
            ],
        ]);
    }
}
