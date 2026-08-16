<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Agent\Concerns\ResolvesAgent;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use ResolvesAgent;

    public function index(Request $request): Response
    {
        $agent = $this->currentAgent($request);

        $customersOnboarded = $agent->onboardedUsers()->count();
        $vehiclesOnboarded = $agent->vehicles()->count();
        $onboardedThisMonth = $agent->onboardedUsers()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $recentCustomers = $agent->onboardedUsers()
            ->withCount('vehicles')
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'vehiclesCount' => $user->vehicles_count,
                'createdAt' => $user->created_at?->toIso8601String(),
            ]);

        return Inertia::render('agent/dashboard', [
            'stats' => [
                'customersOnboarded' => $customersOnboarded,
                'vehiclesOnboarded' => $vehiclesOnboarded,
                'onboardedThisMonth' => $onboardedThisMonth,
            ],
            'recentCustomers' => $recentCustomers,
        ]);
    }
}
