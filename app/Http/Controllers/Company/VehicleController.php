<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Http\Controllers\Company\Concerns\ValidatesTrackerImei;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Vehicle;
use App\Services\SystemLogService;
use App\Services\Tracking\TraccarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\SimpleExcel\SimpleExcelReader;
use Spatie\SimpleExcel\SimpleExcelWriter;

class VehicleController extends Controller
{
    use ResolvesCompany, ValidatesTrackerImei;

    public function index(Request $request): Response
    {
        $company = $this->currentCompany($request);

        $vehicles = $company->vehicles()
            ->withCount(['faults' => fn ($query) => $query->whereNull('cleared_at')])
            ->with('trackerState')
            ->orderBy('license_plate')
            ->paginate(15)
            ->withQueryString()
            ->through($this->mapVehicleSummary(...));

        return Inertia::render('company/vehicles/index', [
            'vehicles' => $vehicles,
        ]);
    }

    public function store(Request $request, SystemLogService $systemLog, TraccarService $traccar): RedirectResponse
    {
        $company = $this->currentCompany($request);
        $validated = $this->validatedVehicle($request);

        $this->assertTrackerImeiIsValid($validated['obd_device_imei'] ?? null);

        $vehicle = $company->vehicles()->create($validated);

        $this->syncTraccarDeviceId(
            $vehicle,
            $traccar
        );

        // Use live tracker odometer if already reporting, or onboarding mileage if entered;
        // otherwise leave null so CheckMaintenanceSchedules locks in the first valid tracker ping.
        $initialOdometer = $vehicle->trackerState?->odometer ?? $vehicle->mileage;
        $baselineOdometer = ($initialOdometer !== null && $initialOdometer > 0)
            ? (int) round((float) $initialOdometer)
            : null;

        $vehicle->maintenanceSchedules()->createMany([
            [
                'name' => 'Oil Change',
                'distance_interval_km' => 5000,
                'time_interval_days' => 180,
                'baseline_odometer_km' => $baselineOdometer,
                'baseline_started_at' => now(),
                'active' => true,
            ],
            [
                'name' => 'Tire Rotation',
                'distance_interval_km' => 10000,
                'time_interval_days' => 365,
                'baseline_odometer_km' => $baselineOdometer,
                'baseline_started_at' => now(),
                'active' => true,
            ],
            [
                'name' => 'General Service',
                'distance_interval_km' => 20000,
                'time_interval_days' => 365,
                'baseline_odometer_km' => $baselineOdometer,
                'baseline_started_at' => now(),
                'active' => true,
            ],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Vehicle added.']);

        $systemLog->log(
            event: 'vehicle.created',
            description: "Created vehicle {$vehicle->license_plate} ({$vehicle->name}).",
            subject: $vehicle,
            company: $company,
        );

        return back();
    }

    public function update(Request $request, SystemLogService $systemLog, TraccarService $traccar): RedirectResponse
    {
        $company = $this->currentCompany($request);
        $vehicle = $company->vehicles()->findOrFail((string) $request->route('vehicle'));

        $validated = $this->validatedVehicle($request);

        if (($validated['obd_device_imei'] ?? null) !== $vehicle->obd_device_imei) {
            $this->assertTrackerImeiIsValid($validated['obd_device_imei'] ?? null);
        }

        $original = $vehicle->getOriginal();

        $vehicle->fill($validated)->save();

        if ($vehicle->wasChanged('obd_device_imei')) {
            $this->syncTraccarDeviceId(
                $vehicle,
                $traccar
            );
        }

        $systemLog->log(
            event: 'vehicle.updated',
            description: "Updated vehicle {$vehicle->license_plate}.",
            subject: $vehicle,
            company: $company,
            metadata: [
                'before' => $original,
                'changes' => $vehicle->getChanges(),
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Vehicle updated.']);

        return back();
    }

    public function destroy(Request $request, SystemLogService $systemLog): RedirectResponse
    {
        $company = $this->currentCompany($request);
        $vehicle = $company->vehicles()->findOrFail((string) $request->route('vehicle'));

        $systemLog->log(
            event: 'vehicle.deleted',
            description: "Deleted vehicle {$vehicle->license_plate}.",
            subject: $vehicle,
            company: $company,
            metadata: [
                'license_plate' => $vehicle->license_plate,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
            ],
        );

        $vehicle->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Vehicle removed.']);

        return back();
    }

    /** @return array<string, mixed> */
    protected function validatedVehicle(Request $request): array
    {
        return $request->validate([
            'license_plate' => ['required', 'string', 'max:50'],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'string', 'max:10'],
            'color' => ['nullable', 'string', 'max:50'],
            'category' => ['required', 'string', 'max:50'],
            'status' => ['required', Rule::in(['active', 'maintenance'])],
            'mileage' => ['required', 'numeric', 'min:0'],
            'vin' => ['nullable', 'string', 'max:50'],
            'obd_device_id' => ['nullable', 'string', 'max:100'],
            'obd_device_imei' => ['nullable', 'string', 'max:50'],
            'tracker_phone_number' => ['nullable', 'string', 'max:50'],
            'traccar_device_id' => ['nullable', 'integer'],
        ]);
    }

    protected const IMPORT_COLUMNS = [
        'License Plate' => 'license_plate',
        'Make' => 'make',
        'Model' => 'model',
        'Year' => 'year',
        'Color' => 'color',
        'Category' => 'category',
        'Status' => 'status',
        'Mileage' => 'mileage',
        'VIN' => 'vin',
        'Tracker Device ID' => 'obd_device_id',
        'Tracker IMEI' => 'obd_device_imei',
        'Tracker Phone Number' => 'tracker_phone_number',
    ];

    public function template(Request $request): void
    {
        $writer = SimpleExcelWriter::streamDownload('vehicle-import-template.csv');

        $writer->addRow([
            'License Plate' => 'AAA-123-BC',
            'Make' => 'Toyota',
            'Model' => 'Hilux',
            'Year' => '2021',
            'Color' => 'White',
            'Category' => 'truck',
            'Status' => 'active',
            'Mileage' => '25000',
            'VIN' => '',
            'Tracker Device ID' => '',
            'Tracker IMEI' => '',
            'Tracker Phone Number' => '',
        ]);

        $writer->toBrowser();
    }

    public function import(Request $request, SystemLogService $systemLog): RedirectResponse
    {
        $company = $this->currentCompany($request);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $rows = SimpleExcelReader::create($request->file('file')->getRealPath(), 'csv')->getRows();

        $created = 0;
        $errors = [];
        $rowNumber = 1;

        foreach ($rows as $row) {
            $rowNumber++;

            $attributes = [];
            foreach (self::IMPORT_COLUMNS as $header => $field) {
                $attributes[$field] = trim((string) ($row[$header] ?? ''));
            }

            if ($attributes['license_plate'] === '') {
                continue;
            }

            $validator = validator($attributes, [
                'license_plate' => ['required', 'string', 'max:50'],
                'make' => ['nullable', 'string', 'max:100'],
                'model' => ['nullable', 'string', 'max:100'],
                'year' => ['nullable', 'string', 'max:10'],
                'color' => ['nullable', 'string', 'max:50'],
                'category' => ['nullable', 'string', 'max:50'],
                'status' => ['nullable', Rule::in(['active', 'maintenance'])],
                'mileage' => ['nullable', 'numeric', 'min:0'],
                'vin' => ['nullable', 'string', 'max:50'],
                'obd_device_id' => ['nullable', 'string', 'max:100'],
                'obd_device_imei' => ['nullable', 'string', 'max:50'],
                'tracker_phone_number' => ['nullable', 'string', 'max:50'],
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNumber} ({$attributes['license_plate']}): ".$validator->errors()->first();

                continue;
            }

            $valid = $validator->validated();
            $valid['category'] = $valid['category'] ?: 'car';
            $valid['status'] = $valid['status'] ?: 'active';
            $valid['mileage'] = $valid['mileage'] ?: 0;

            if (! empty($valid['obd_device_imei'])) {
                try {
                    $this->assertTrackerImeiIsValid($valid['obd_device_imei']);
                } catch (ValidationException $exception) {
                    $errors[] = "Row {$rowNumber} ({$attributes['license_plate']}): ".$exception->validator->errors()->first();

                    continue;
                }
            }

            $company->vehicles()->create($valid);
            $created++;
        }

        $message = "{$created} vehicle(s) imported.";

        if ($errors !== []) {
            $message .= ' '.count($errors).' row(s) skipped.';
        }

        $systemLog->log(
            event: 'vehicle.imported',
            description: "Imported {$created} vehicle(s).",
            company: $company,
            metadata: [
                'created_count' => $created,
                'skipped_count' => count($errors),
            ],
        );

        Inertia::flash('toast', [
            'type' => $errors === [] ? 'success' : 'warning',
            'message' => $message,
        ]);

        if ($errors !== []) {
            Inertia::flash('importErrors', $errors);
        }

        return back();
    }

    public function show(Request $request): Response
    {
        $company = $this->currentCompany($request);

        $model = $company->vehicles()
            ->with([
                'trackerState',
                'trips' => fn ($query) => $query->latest('start_time')->limit(20),
                'faults' => fn ($query) => $query->latest('log_time')->limit(30),
                'alarms' => fn ($query) => $query->latest('gps_time')->limit(30),
                'events' => fn ($query) => $query->latest('event_time')->limit(30),
                'documents.document',
                'serviceEntries' => fn ($query) => $query->latest('starts_at')->limit(10),
                'issues' => fn ($query) => $query->latest('reported_at')->limit(20),
                // 'maintenanceSchedules' => fn ($query) => $query->where('active', true)->latest(),
                // 'maintenanceRecords' => fn ($query) => $query->latest('maintained_at')->limit(20),
                // 'maintenanceAlerts' => fn ($query) => $query->where('acknowledged', false)->latest('alerted_at'),
                // 1. In your ->with([...]) query inside VehicleController@show:
                'maintenanceSchedules' => fn ($query) => $query
                    ->where('active', true)
                    ->latest(),

                'maintenanceRecords' => fn ($query) => $query
                    ->with('maintenanceSchedule:id,name') // <-- Add this so history shows the schedule name
                    ->latest('maintained_at')
                    ->limit(20),

                'maintenanceAlerts' => fn ($query) => $query
                    ->where('acknowledged', false)
                    ->latest('alerted_at'),
            ])
            ->findOrFail((string) $request->route('vehicle'));

            Log::info('Fleetwize Vehicle Fuel Consumption - Tracker State', [
                'vehicle_id' => $model->id,
                'vehicle_name' => $model->name,
                'tracker_imei' => $model->obd_device_imei,
                'fuel_consumed' => $model->trackerState?->fuel_consumed,
            ]);

        return Inertia::render('company/vehicles/show', [
            'vehicle' => [
                'id' => $model->id,
                'name' => $model->name,
                'licensePlate' => $model->license_plate,
                'make' => $model->make,
                'model' => $model->model,
                'year' => $model->year,
                'color' => $model->color,
                'category' => $model->category,
                'status' => $model->status,
                'mileage' => $model->mileage,
                'vin' => $model->vin,
                'trackerImei' => $model->obd_device_imei,
                'trackerPhoneNumber' => $model->tracker_phone_number,
                'isOnline' => $model->trackerState?->isOnline() ?? false,
                'liveState' => $model->trackerState ? [
                    'latitude' => $model->trackerState->latitude,
                    'longitude' => $model->trackerState->longitude,
                    'speed' => $model->trackerState->speed,
                    'heading' => $model->trackerState->heading,
                    'ignitionOn' => $model->trackerState->ignition_on,
                    'fuelLevel' => $model->trackerState->fuel_level,
                    'fuelLevelPercent' => $model->trackerState->fuel_level_percent,
                    'fuelConsumed' => $model->trackerState->fuel_consumed,
                    'batteryVoltage' => $model->trackerState->battery_voltage,
                    'trackerBatteryVoltage' => $model->trackerState->tracker_battery_voltage,
                    'reportedAt' => $model->trackerState->reported_at?->toIso8601String(),
                    'engineRpm' => $model->trackerState->engine_rpm,
                    'engineLoad' => $model->trackerState->engine_load,
                    'coolantTemp' => $model->trackerState->coolant_temp,
                    'obdSpeed' => $model->trackerState->obd_speed,
                    'isMoving' => $model->trackerState->is_moving,
                    'batteryLevel' => $model->trackerState->battery_level,
                    'satelliteCount' => $model->trackerState->satellite_count,
                    'signalStrength' => $model->trackerState->signal_strength,
                    'engineHours' => $model->trackerState->engine_hours,
                    'isBlocked' => $model->trackerState->is_blocked,
                    'isCharging' => $model->trackerState->is_charging,
                    'uniqueId' => $model->trackerState->unique_id,
                    'deviceStatus' => $model->trackerState->device_status,
                    'protocol' => $model->trackerState->protocol,
                    'altitude' => $model->trackerState->altitude,
                    'gpsValid' => $model->trackerState->gps_valid,
                    'deviceTime' => $model->trackerState->device_time?->toIso8601String(),
                    'serverTime' => $model->trackerState->server_time?->toIso8601String(),
                    'odometer' => $model->trackerState->odometer,
                    'obdOdometer' => $model->trackerState->obd_odometer,
                    'totalDistance' => $model->trackerState->total_distance,
                    'hardCorneringCount' => $model->trackerState->hard_cornering_count,
                    'hardAccelerationCount' => $model->trackerState->hard_acceleration_count,
                    'hardDecelerationCount' => $model->trackerState->hard_deceleration_count,
                    'dtcCount' => $model->trackerState->dtc_count,
                    'dtcCodes' => $model->trackerState->dtc_codes,
                    'status' => $model->trackerState->status(),
                ] : null,
                'trips' => $model->trips->map(function ($trip) {

                    Log::info('Fleetwize Vehicle Fuel Consumption - Trip', [
                        'trip_id' => $trip->id,
                        'vehicle_id' => $trip->vehicle_id,
                        'start_time' => $trip->start_time,
                        'end_time' => $trip->end_time,
                        'fuel_consumed' => $trip->fuel_consumed,
                    ]);

                    return [
                        'id' => $trip->id,
                        'startTime' => $trip->start_time?->toIso8601String(),
                        'endTime' => $trip->end_time?->toIso8601String(),
                        'durationSeconds' => $trip->duration_seconds,
                        'distanceKm' => $trip->distance_km,
                        'averageSpeed' => $trip->average_speed_km_per_hr,
                        'maxSpeed' => $trip->max_speed,
                        'fuelConsumed' => $trip->fuel_consumed !== null ? (float) $trip->fuel_consumed : null,
                        // 'fuelConsumed' => $trip->fuel_consumed,
                        'startOdometer' => $trip->start_odometer,
                        'endOdometer' => $trip->end_odometer,
                        'startPositionId' => $trip->start_position_id,
                        'endPositionId' => $trip->end_position_id,
                        'startLatitude' => $trip->start_latitude,
                        'startLongitude' => $trip->start_longitude,
                        'endLatitude' => $trip->end_latitude,
                        'endLongitude' => $trip->end_longitude,
                        'startAddress' => $trip->start_address,
                        'endAddress' => $trip->end_address,
                        'driverUniqueId' => $trip->driver_unique_id,
                        'driverName' => $trip->driver_name,
                    ];
                }),

                'faults' => $model->faults->map(fn ($fault) => [
                    'id' => $fault->id,
                    'code' => $fault->obd_code,
                    'meaning' => $fault->meaning,
                    'severity' => $fault->severity,
                    'logTime' => $fault->log_time?->toIso8601String(),
                    'clearedAt' => $fault->cleared_at?->toIso8601String(),
                ]),
                'alarms' => $model->alarms->map(fn ($alarm) => [
                    'id' => $alarm->id,
                    'code' => $alarm->alarm_type,
                    'meaning' => $alarm->alarm_description ?? $alarm->description,
                    'severity' => $alarm->severity(),
                    'logTime' => $alarm->gps_time?->toIso8601String(),
                    'clearedAt' => $alarm->acknowledged_at?->toIso8601String(),
                ]),
                'events' => $model->events->map(fn ($event) => [
                    'id' => $event->id,
                    'eventType' => $event->event_type,
                    'alarm' => $event->alarm,
                    'logTime' => $event->event_time?->toIso8601String(),
                    'attributes' => $event->attributes ?? [],
                ]),
                'documents' => $model->documents->map(fn ($doc) => [
                    'id' => $doc->id,
                    'documentId' => $doc->document_id,
                    'name' => $doc->document?->title,
                    'documentNumber' => $doc->document_number,
                    'lastRenewedAt' => $doc->last_renewed_at?->toDateString(),
                    'expiresAt' => $doc->expires_at?->toDateString(),
                    'status' => $doc->expiry_status,
                ]),
                'serviceEntries' => $model->serviceEntries->map(fn ($entry) => [
                    'id' => $entry->id,
                    'startsAt' => $entry->starts_at?->toIso8601String(),
                    'endsAt' => $entry->ends_at?->toIso8601String(),
                    'comments' => $entry->comments,
                ]),

                'maintenanceSchedules' => $model->maintenanceSchedules->map(function ($schedule) use ($model) {
                    // Find the most recent service record for this specific schedule
                    $latestRecord = $model->maintenanceRecords
                        ->where('maintenance_schedule_id', $schedule->id)
                        ->sortByDesc('maintained_at')
                        ->first();

                    $currentOdometer = $model->trackerState?->odometer ?? $model->mileage;
                    $lastServiceOdometer = $latestRecord?->odometer_km ?? $schedule->baseline_odometer_km;
                    $lastServiceDate = $latestRecord?->maintained_at ?? $schedule->baseline_started_at;

                    $distanceSinceServiceKm = ($currentOdometer !== null && $lastServiceOdometer !== null)
                        ? max(0, (int) round($currentOdometer - $lastServiceOdometer))
                        : null;

                    $remainingKm = ($schedule->distance_interval_km !== null && $distanceSinceServiceKm !== null)
                        ? (int) ($schedule->distance_interval_km - $distanceSinceServiceKm)
                        : null;

                    $daysRemaining = ($schedule->time_interval_days !== null && $lastServiceDate !== null)
                        ? (int) now()->diffInDays($lastServiceDate->copy()->addDays($schedule->time_interval_days), false)
                        : null;

                    // Status logic matching the meeting specification (Alert when <= 500 km)
                    $status = 'healthy';
                    if (($remainingKm !== null && $remainingKm <= 0) || ($daysRemaining !== null && $daysRemaining <= 0)) {
                        $status = 'overdue';
                    } elseif (($remainingKm !== null && $remainingKm <= 500) || ($daysRemaining !== null && $daysRemaining <= 14)) {
                        $status = 'due_soon';
                    }

                    return [
                        'id' => $schedule->id,
                        'name' => $schedule->name,
                        'distanceIntervalKm' => $schedule->distance_interval_km,
                        'timeIntervalDays' => $schedule->time_interval_days,
                        'baselineOdometerKm' => $schedule->baseline_odometer_km,
                        'lastServiceOdometerKm' => $lastServiceOdometer,
                        'lastMaintainedAt' => $lastServiceDate?->toDateString(),
                        'distanceSinceServiceKm' => $distanceSinceServiceKm,
                        'remainingKm' => $remainingKm,
                        'daysRemaining' => $daysRemaining,
                        'status' => $status,
                        'active' => $schedule->active,
                    ];
                }),

                'maintenanceRecords' => $model->maintenanceRecords->map(fn ($record) => [
                    'id' => $record->id,
                    'scheduleName' => $record->maintenanceSchedule?->name ?? 'General Service',
                    'maintainedAt' => $record->maintained_at?->toDateString(),
                    'odometerKm' => $record->odometer_km,
                    'notes' => $record->notes,
                    'maintenanceScheduleId' => $record->maintenance_schedule_id ?? null,
                ]),

                'maintenanceAlerts' => $model->maintenanceAlerts->map(fn ($alert) => [
                    'id' => $alert->id,
                    'maintenanceScheduleId' => $alert->maintenance_schedule_id,
                    'title' => $alert->title,
                    'description' => $alert->description,
                    'alertedAt' => $alert->alerted_at?->toDateString(),
                    'acknowledged' => $alert->acknowledged,
                ]),

                'issues' => $model->issues->map(fn ($issue) => [
                    'id' => $issue->id,
                    'summary' => $issue->summary,
                    'priority' => $issue->priority,
                    'status' => $issue->status,
                    'reportedAt' => $issue->reported_at?->toIso8601String(),
                ]),
            ],
            // Pass document options at root level with defensive fallback
            'documentOptions' => \App\Models\Document::query()
                ->orderBy('title')
                ->get(['id', 'title']),
        ]);
    }

    /** @return array<string, mixed> */
    protected function mapVehicleSummary(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'name' => $vehicle->name,
            'licensePlate' => $vehicle->license_plate,
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'category' => $vehicle->category,
            'status' => $vehicle->status,
            'mileage' => $vehicle->mileage,
            'isOnline' => $vehicle->trackerState?->isOnline() ?? false,
            'speed' => $vehicle->trackerState?->speed,
            'fuelLevel' => $vehicle->trackerState?->fuel_level,
            'openFaultsCount' => $vehicle->faults_count,
            'lastSeenAt' => $vehicle->trackerState?->reported_at?->toIso8601String(),
        ];
    }
    
    protected function syncTraccarDeviceId(Vehicle $vehicle, TraccarService $traccar): void 
    {
        if (! $vehicle->obd_device_imei) {
            return;
        }

        $device = $traccar->findDeviceByImei(
            (string) $vehicle->obd_device_imei
        );

        if (! $device) {
            return;
        }

        $vehicle->updateQuietly([
            'traccar_device_id' => $device['id'],
        ]);
    }

    public function sendCommand(Request $request, string $company_slug, Vehicle $vehicle, TraccarService $traccar)
    {
        $company = $this->currentCompany($request);
        abort_unless($vehicle->company_id === $company->id, 404);

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:immobilize,restore,scan_dtc,provision_teltonika,custom'],
            'custom_command' => ['nullable', 'required_if:action,custom', 'string', 'max:255'],
        ]);

        if (! $vehicle->traccar_device_id) {
            return back()->with('error', 'This vehicle does not have a linked Traccar device ID yet.');
        }

        $protocol = $vehicle->trackerState?->protocol ?? 'teltonika';
        $deviceId = (int) $vehicle->traccar_device_id;

        // Map high-level fleet action to protocol-specific command
        [$type, $commandData] = match ($validated['action']) {
            // Teltonika triggers DOUT1 immobilizer relay via "setdigout 1" / "setdigout 0"
            // JT808 / VD40 uses Traccar's native engineStop / engineResume command type
            'immobilize' => $protocol === 'teltonika'
                ? ['custom', 'setdigout 1']
                : ['engineStop', ''],

            'restore' => $protocol === 'teltonika'
                ? ['custom', 'setdigout 0']
                : ['engineResume', ''],

            'scan_dtc' => $protocol === 'teltonika'
                ? ['custom', 'readio 30']
                : ['custom', 'OBD,DTC'],

            'provision_teltonika' => [
                'custom',
                'setparam 11000:1;11004:25;11005:28;11006:30;11007:0;40100:1;40430:1;11500:2;11200:1',
            ],

            'custom' => ['custom', trim((string) $validated['custom_command'])],
        };

        $traccar->sendCommand($deviceId, $commandData, $type);

        // Update local blocked state immediately if immobilizing/restoring
        if ($validated['action'] === 'immobilize' && $vehicle->trackerState) {
            $vehicle->trackerState->update(['is_blocked' => true]);
        } elseif ($validated['action'] === 'restore' && $vehicle->trackerState) {
            $vehicle->trackerState->update(['is_blocked' => false]);
        }

        // FRD VC-02 & RBAC-03: Log command with timestamp, issuing user, and vehicle ID
        Log::info('Vehicle remote command dispatched', [
            'company_id' => $company->id,
            'vehicle_id' => $vehicle->id,
            'traccar_device_id' => $deviceId,
            'user_id' => $request->user()?->id,
            'user_email' => $request->user()?->email,
            'action' => $validated['action'],
            'type' => $type,
            'command' => $commandData,
            'ip' => $request->ip(),
        ]);

        return back()->with('success', 'Command sent to vehicle tracker.');
    }
}
