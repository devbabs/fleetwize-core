<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Models\VehicleAlarm;
use App\Models\VehicleEvent;
use App\Services\SystemLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlarmController extends Controller
{
    use ResolvesCompany;

    public function index(Request $request): Response
    {
        $company = $this->currentCompany($request);

        $vehicles = $company->vehicles()
            ->select('id', 'license_plate')
            ->orderBy('license_plate')
            ->get();

        $vehicleIds = $vehicles->pluck('id');

        $eventTypes = VehicleEvent::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->whereNotNull('event_type')
            ->distinct()
            ->orderBy('event_type')
            ->pluck('event_type');

        $events = VehicleEvent::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->with('vehicle:id,license_plate,make,model')
            ->when(
                $request->filled('vehicle_id'),
                fn ($query) => $query->where(
                    'vehicle_id',
                    $request->vehicle_id
                )
            )
            ->when(
                $request->filled('event_type'),
                fn ($query) => $query->where(
                    'event_type',
                    $request->event_type
                )
            )
            ->latest('event_time')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (VehicleEvent $event) => [
                'id' => $event->id,
                'vehicle' => $event->vehicle?->license_plate ?? '—',
                'vehicleId' => $event->vehicle_id,
                'eventType' => $event->event_type,
                'alarm' => $event->alarm,
                'logTime' => $event->event_time?->toIso8601String(),
                'attributes' => $event->attributes ?? [],
            ]);

        return Inertia::render('company/alarms/index', [
            'events' => $events,
            'vehicles' => $vehicles,
            'eventTypes' => $eventTypes,
            'filters' => [
                'vehicle_id' => $request->vehicle_id,
                'event_type' => $request->event_type,
            ],
        ]);
    }

    public function clear(Request $request, SystemLogService $systemLog): RedirectResponse
    {
        $company = $this->currentCompany($request);
        $vehicleIds = $company->vehicles()->pluck('id');

        $alarm = VehicleAlarm::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->findOrFail((string) $request->route('fault'));

        $alarm->acknowledged_at = now();
        $alarm->save();

         $systemLog->log(
            event: 'alarm.acknowledged',
            description: "Acknowledged alarm {$alarm->alarm_type} for vehicle ID {$alarm->vehicle_id}.",
            subject: $alarm,
            company: $company,
            metadata: [
                'alarm_id' => $alarm->id,
                'alarm_type' => $alarm->alarm_type,
                'vehicle_id' => $alarm->vehicle_id,
                'gps_time' => $alarm->gps_time?->toIso8601String(),
            ],
        );

        return back();
    }
}
