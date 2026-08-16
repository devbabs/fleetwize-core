<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Models\VehicleServiceEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceController extends Controller
{
    use ResolvesCompany;

    public function index(Request $request): Response
    {
        $company = $this->currentCompany($request);

        $baseQuery = fn () => VehicleServiceEntry::query()
            ->where('company_id', $company->id)
            ->with(['vehicle:id,license_plate,make,model', 'tasks.serviceTask:id,name']);

        $overdue = $baseQuery()
            ->whereNull('ends_at')
            ->where('starts_at', '<', now())
            ->orderBy('starts_at')
            ->get()
            ->map($this->mapEntry(...));

        $upcoming = $baseQuery()
            ->whereNull('ends_at')
            ->whereBetween('starts_at', [now(), now()->addDays(30)])
            ->orderBy('starts_at')
            ->get()
            ->map($this->mapEntry(...));

        $history = $baseQuery()
            ->whereNotNull('ends_at')
            ->latest('ends_at')
            ->paginate(15)
            ->withQueryString()
            ->through($this->mapEntry(...));

        return Inertia::render('company/maintenance/index', [
            'overdue' => $overdue,
            'upcoming' => $upcoming,
            'history' => $history,
        ]);
    }

    /** @return array<string, mixed> */
    protected function mapEntry(VehicleServiceEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'vehicle' => $entry->vehicle->license_plate ?? trim("{$entry->vehicle->make} {$entry->vehicle->model}"),
            'vehicleId' => $entry->vehicle_id,
            'startsAt' => $entry->starts_at?->toIso8601String(),
            'endsAt' => $entry->ends_at?->toIso8601String(),
            'comments' => $entry->comments,
            'tasks' => $entry->tasks->map(fn ($task) => $task->serviceTask->name)->filter()->values(),
        ];
    }
}
