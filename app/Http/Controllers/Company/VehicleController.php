<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Http\Controllers\Company\Concerns\ValidatesTrackerImei;
use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(Request $request): RedirectResponse
    {
        $company = $this->currentCompany($request);
        $validated = $this->validatedVehicle($request);

        $this->assertTrackerImeiIsValid($validated['obd_device_imei'] ?? null);

        $company->vehicles()->create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Vehicle added.']);

        return back();
    }

    public function update(Request $request): RedirectResponse
    {
        $company = $this->currentCompany($request);
        $vehicle = $company->vehicles()->findOrFail((string) $request->route('vehicle'));

        $validated = $this->validatedVehicle($request);

        if (($validated['obd_device_imei'] ?? null) !== $vehicle->obd_device_imei) {
            $this->assertTrackerImeiIsValid($validated['obd_device_imei'] ?? null);
        }

        $vehicle->fill($validated)->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Vehicle updated.']);

        return back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        $company = $this->currentCompany($request);
        $vehicle = $company->vehicles()->findOrFail((string) $request->route('vehicle'));

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

    public function import(Request $request): RedirectResponse
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
                'documents.document',
                'serviceEntries' => fn ($query) => $query->latest('starts_at')->limit(10),
                'issues' => fn ($query) => $query->latest('reported_at')->limit(20),
            ])
            ->findOrFail((string) $request->route('vehicle'));

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
                    'batteryVoltage' => $model->trackerState->battery_voltage,
                    'reportedAt' => $model->trackerState->reported_at?->toIso8601String(),
                ] : null,
                'trips' => $model->trips->map(fn ($trip) => [
                    'id' => $trip->id,
                    'startTime' => $trip->start_time?->toIso8601String(),
                    'endTime' => $trip->end_time?->toIso8601String(),
                    'distanceKm' => $trip->distance_km,
                    'averageSpeed' => $trip->average_speed_km_per_hr,
                    'maxSpeed' => $trip->max_speed_km_per_hr,
                    'fuelConsumed' => $trip->fuel_consumed,
                ]),
                'faults' => $model->faults->map(fn ($fault) => [
                    'id' => $fault->id,
                    'code' => $fault->obd_code,
                    'meaning' => $fault->meaning,
                    'severity' => $fault->severity,
                    'logTime' => $fault->log_time?->toIso8601String(),
                    'clearedAt' => $fault->cleared_at?->toIso8601String(),
                ]),
                'documents' => $model->documents->map(fn ($doc) => [
                    'id' => $doc->id,
                    'title' => $doc->document_title ?? $doc->document->title,
                    'expiresAt' => $doc->expires_at?->toIso8601String(),
                    'expiryStatus' => $doc->expiry_status,
                ]),
                'serviceEntries' => $model->serviceEntries->map(fn ($entry) => [
                    'id' => $entry->id,
                    'startsAt' => $entry->starts_at?->toIso8601String(),
                    'endsAt' => $entry->ends_at?->toIso8601String(),
                    'comments' => $entry->comments,
                ]),
                'issues' => $model->issues->map(fn ($issue) => [
                    'id' => $issue->id,
                    'summary' => $issue->summary,
                    'priority' => $issue->priority,
                    'status' => $issue->status,
                    'reportedAt' => $issue->reported_at?->toIso8601String(),
                ]),
            ],
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
}
