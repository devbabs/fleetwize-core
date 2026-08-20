<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Models\Geofence;
use App\Models\Vehicle;
use App\Services\Tracking\TraccarService;
use App\Support\GeofenceWkt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class GeofenceController extends Controller
{
    use ResolvesCompany;

    public function index(Request $request): Response
    {
        $company = $this->currentCompany($request);

        $geofences = $company->geofences()
            ->with('vehicles:id,license_plate,make,model')
            ->latest()
            ->get()
            ->map(fn (Geofence $geofence) => [
                'id' => $geofence->id,
                'name' => $geofence->name,
                'shape' => $geofence->shape,
                'centerLatitude' => $geofence->center_latitude,
                'centerLongitude' => $geofence->center_longitude,
                'radiusMeters' => $geofence->radius_meters,
                'polygon' => $geofence->polygon,
                'color' => $geofence->color,
                'synced' => $geofence->traccar_geofence_id !== null,
                'vehicleIds' => $geofence->vehicles->pluck('id'),
                'vehicles' => $geofence->vehicles->map(fn (Vehicle $vehicle) => [
                    'id' => $vehicle->id,
                    'label' => $this->vehicleLabel($vehicle),
                ]),
            ]);

        return Inertia::render('company/geofencing/index', [
            'geofences' => $geofences,
            'vehicleOptions' => $company->vehicles()
                ->orderBy('license_plate')
                ->get(['id', 'license_plate', 'make', 'model'])
                ->map(fn (Vehicle $vehicle) => [
                    'id' => $vehicle->id,
                    'label' => $this->vehicleLabel($vehicle),
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->currentCompany($request);
        $validated = $this->validateGeofence($request, $company->id);

        $geofence = new Geofence;
        $geofence->company_id = $company->id;
        $this->fillGeofence($geofence, $validated);
        $geofence->save();

        $synced = $this->syncToTraccar($geofence);
        $this->syncVehicles($geofence, $validated['vehicle_ids'] ?? []);

        $this->flashSyncResult($synced, 'Geofence created.');

        return back();
    }

    public function update(Request $request): RedirectResponse
    {
        $company = $this->currentCompany($request);
        $geofence = $company->geofences()->findOrFail((string) $request->route('geofence'));

        $validated = $this->validateGeofence($request, $company->id);

        $this->fillGeofence($geofence, $validated);
        $geofence->save();

        $synced = $this->syncToTraccar($geofence);
        $this->syncVehicles($geofence, $validated['vehicle_ids'] ?? []);

        $this->flashSyncResult($synced, 'Geofence updated.');

        return back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        $company = $this->currentCompany($request);
        $geofence = $company->geofences()->findOrFail((string) $request->route('geofence'));

        $traccarGeofenceId = $geofence->traccar_geofence_id;

        $geofence->delete();

        if ($traccarGeofenceId) {
            try {
                app(TraccarService::class)->deleteGeofence($traccarGeofenceId);
            } catch (Throwable $e) {
                // The local row is already gone — this is best-effort
                // cleanup on Traccar's side, not something worth blocking
                // or surfacing to the user over.
                Log::warning('Failed to delete geofence on Traccar.', [
                    'geofence_id' => $geofence->id,
                    'traccar_geofence_id' => $traccarGeofenceId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Geofence removed.']);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateGeofence(Request $request, int $companyId): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'shape' => ['required', Rule::in(['circle', 'polygon'])],
            'color' => ['nullable', 'string', 'max:20'],
            'center_latitude' => ['required_if:shape,circle', 'nullable', 'numeric', 'between:-90,90'],
            'center_longitude' => ['required_if:shape,circle', 'nullable', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required_if:shape,circle', 'nullable', 'numeric', 'min:10'],
            'polygon' => ['required_if:shape,polygon', 'nullable', 'array', 'min:3'],
            'polygon.*' => ['array', 'size:2'],
            'polygon.*.0' => ['numeric', 'between:-90,90'],
            'polygon.*.1' => ['numeric', 'between:-180,180'],
            'vehicle_ids' => ['nullable', 'array'],
            'vehicle_ids.*' => ['integer', Rule::exists('vehicles', 'id')->where('company_id', $companyId)],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function fillGeofence(Geofence $geofence, array $validated): void
    {
        $geofence->name = $validated['name'];
        $geofence->shape = $validated['shape'];
        $geofence->center_latitude = $validated['center_latitude'] ?? null;
        $geofence->center_longitude = $validated['center_longitude'] ?? null;
        $geofence->radius_meters = $validated['radius_meters'] ?? null;
        $geofence->polygon = $validated['polygon'] ?? null;
        $geofence->color = $validated['color'] ?? null;
    }

    /**
     * Creates or updates the mirrored geofence on Traccar. The local row is
     * already saved by the time this runs — a failure here is logged and
     * reported via a toast, but never rolled back, since our own DB is the
     * source of truth and this can simply be retried on the next save.
     */
    protected function syncToTraccar(Geofence $geofence): bool
    {
        $area = $geofence->shape === 'circle'
            ? GeofenceWkt::circle((float) $geofence->center_latitude, (float) $geofence->center_longitude, (float) $geofence->radius_meters)
            : GeofenceWkt::polygon($geofence->polygon ?? []);

        $traccar = app(TraccarService::class);

        try {
            if ($geofence->traccar_geofence_id) {
                $traccar->updateGeofence($geofence->traccar_geofence_id, $geofence->name, $area);
            } else {
                $created = $traccar->createGeofence($geofence->name, $area);
                $geofence->traccar_geofence_id = $created['id'];
                $geofence->save();
            }

            return true;
        } catch (Throwable $e) {
            Log::warning('Failed to sync geofence to Traccar.', [
                'geofence_id' => $geofence->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Reconciles the vehicle_geofences pivot against the submitted vehicle
     * ids, and mirrors each change into Traccar's device permissions. Local
     * pivot rows are always written regardless of whether the Traccar call
     * for that specific vehicle succeeds — a per-vehicle Traccar hiccup
     * shouldn't leave the assignment silently missing from our own records.
     *
     * @param  array<int, int>  $vehicleIds
     */
    protected function syncVehicles(Geofence $geofence, array $vehicleIds): void
    {
        $currentIds = $geofence->vehicles()->pluck('vehicles.id')->all();
        $added = array_diff($vehicleIds, $currentIds);
        $removed = array_diff($currentIds, $vehicleIds);

        if ($added === [] && $removed === []) {
            return;
        }

        $geofence->vehicles()->sync($vehicleIds);

        if (! $geofence->traccar_geofence_id) {
            return;
        }

        $traccar = app(TraccarService::class);
        $vehicles = Vehicle::query()->whereIn('id', [...$added, ...$removed])->get()->keyBy('id');

        foreach ($added as $vehicleId) {
            $this->linkVehicleGeofence($traccar, $geofence, $vehicles->get($vehicleId));
        }

        foreach ($removed as $vehicleId) {
            $this->unlinkVehicleGeofence($traccar, $geofence, $vehicles->get($vehicleId));
        }
    }

    protected function linkVehicleGeofence(TraccarService $traccar, Geofence $geofence, ?Vehicle $vehicle): void
    {
        if (! $vehicle?->obd_device_imei || ! $geofence->traccar_geofence_id) {
            return;
        }

        try {
            $device = $traccar->findDeviceByImei($vehicle->obd_device_imei);

            if ($device) {
                $traccar->linkGeofenceToDevice($geofence->traccar_geofence_id, (int) $device['id']);
            }
        } catch (Throwable $e) {
            Log::warning('Failed to link vehicle to geofence on Traccar.', [
                'geofence_id' => $geofence->id,
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function unlinkVehicleGeofence(TraccarService $traccar, Geofence $geofence, ?Vehicle $vehicle): void
    {
        if (! $vehicle?->obd_device_imei || ! $geofence->traccar_geofence_id) {
            return;
        }

        try {
            $device = $traccar->findDeviceByImei($vehicle->obd_device_imei);

            if ($device) {
                $traccar->unlinkGeofenceFromDevice($geofence->traccar_geofence_id, (int) $device['id']);
            }
        } catch (Throwable $e) {
            Log::warning('Failed to unlink vehicle from geofence on Traccar.', [
                'geofence_id' => $geofence->id,
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function flashSyncResult(bool $synced, string $successMessage): void
    {
        Inertia::flash('toast', $synced
            ? ['type' => 'success', 'message' => $successMessage]
            : ['type' => 'warning', 'message' => "{$successMessage} It could not sync to the tracking server yet, so it won't be enforced until this succeeds — try saving again shortly."]);
    }

    protected function vehicleLabel(Vehicle $vehicle): string
    {
        return trim(($vehicle->license_plate ?? '').' '.collect([$vehicle->make, $vehicle->model])->filter()->implode(' '));
    }
}
