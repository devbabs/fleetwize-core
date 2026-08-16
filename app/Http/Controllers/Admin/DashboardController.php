<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Company;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $recentCompanies = Company::query()
            ->withCount('vehicles')
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (Company $company) => [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
                'vehiclesCount' => $company->vehicles_count,
                'createdAt' => $company->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/dashboard', [
            'stats' => [
                'totalCompanies' => Company::count(),
                'totalAgents' => Agent::count(),
                'totalVehicles' => Vehicle::count(),
            ],
            'recentCompanies' => $recentCompanies,
        ]);
    }
}
