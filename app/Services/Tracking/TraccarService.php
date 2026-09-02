<?php

namespace App\Services\Tracking;

use App\Models\Vehicle;
use App\Models\VehicleTrackerState;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * On-demand REST pulls against the self-hosted Traccar instance — used for
 * an admin "force refresh" or backfilling state outside the normal webhook
 * flow (see App\Jobs\ProcessTrackerTelemetry for the primary ingestion path).
 */
class TraccarService
{
    public function __construct(
        protected ?string $baseUrl = null,
        protected ?string $username = null,
        protected ?string $password = null,
    ) {
        $this->baseUrl ??= config('fleetwize.traccar.base_url');
        $this->username ??= config('fleetwize.traccar.username');
        $this->password ??= config('fleetwize.traccar.password');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findDeviceByImei(string $imei): ?array
    {
        // Traccar's `uniqueId` filter and `all=true` don't combine correctly
        // for devices with no owning user (e.g. auto-registered via
        // database.registerUnknown), silently returning an empty result.
        // Fetching everything and filtering here avoids that.
        $devices = $this->client()
            ->get('/api/devices', ['all' => 'true'])
            ->throw()
            ->json();

        return collect($devices)->firstWhere('uniqueId', $imei);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function latestPosition(int $deviceId): ?array
    {
        $positions = $this->client()
            ->get('/api/positions', ['all' => 'true'])
            ->throw()
            ->json();

        return collect($positions)->firstWhere('deviceId', $deviceId);
    }

    /**
     * Pull the vehicle's current device + position from Traccar right now
     * and upsert its VehicleTrackerState, independent of the webhook flow.
     */
    public function refreshVehicleState(Vehicle $vehicle): ?VehicleTrackerState
    {
        if (! $vehicle->obd_device_imei) {
            return null;
        }

        $device = $this->findDeviceByImei($vehicle->obd_device_imei);

        if (! $device) {
            return null;
        }

        $position = $this->latestPosition($device['id']);

        if (! $position) {
            return null;
        }

        return VehicleTrackerState::query()->updateOrCreate(
            ['vehicle_id' => $vehicle->id],
            TraccarPayloadNormalizer::normalize($position, $device),
        );
    }

    /**
     * Traccar's own pre-computed trip aggregates for a device over a window
     * — reused rather than building our own trip-boundary detection from
     * the raw position stream. Note: if this ever comes back empty for a
     * device with no owning Traccar user (the same gotcha findDeviceByImei/
     * latestPosition had), try adding ['all' => 'true'] here too — untested,
     * since /api/reports/trips takes a required deviceId and may authorize
     * differently than the list endpoints did.
     *
     * @return array<int, array<string, mixed>>
     */
    public function tripsForDevice(int $deviceId, CarbonInterface $from, CarbonInterface $to): array
    {
        return $this->client()
            ->get('/api/reports/trips', [
                'deviceId' => $deviceId,
                'from' => $from->toIso8601String(),
                'to' => $to->toIso8601String(),
            ])
            ->throw()
            ->json() ?? [];
    }

    public function tripsForDevice(int $deviceId, CarbonInterface $from, CarbonInterface $to): array
    {
        Log::info("Pulling trips for device {$deviceId} from {$from->toIso8601String()} to {$to->toIso8601String()}");

        $response = $this->client()->get('/api/reports/trips', [
            'deviceId' => $deviceId,
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
        ]);

        Log::info('Traccar trips response', [
            'device_id' => $deviceId,
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        $response->throw();

        return $response->json() ?? [];
    }

    /**
     * Devices auto-registered via Traccar's database.registerUnknown have no
     * owner, and stay invisible on the map/API to any user (including
     * admins, without `all=true`) until explicitly linked. Called once per
     * device by ProcessTrackerTelemetry the first time it's seen.
     */
    public function linkDeviceToUser(int $deviceId): void
    {
        $this->client()
            ->post('/api/permissions', [
                'userId' => config('fleetwize.traccar.owner_user_id'),
                'deviceId' => $deviceId,
            ])
            ->throw();
    }

    /**
     * @return array<string, mixed>
     */
    public function createGeofence(string $name, string $area): array
    {
        return $this->client()
            ->post('/api/geofences', ['name' => $name, 'area' => $area])
            ->throw()
            ->json();
    }

    public function updateGeofence(int $traccarGeofenceId, string $name, string $area): void
    {
        $this->client()
            ->put("/api/geofences/{$traccarGeofenceId}", [
                'id' => $traccarGeofenceId,
                'name' => $name,
                'area' => $area,
            ])
            ->throw();
    }

    public function deleteGeofence(int $traccarGeofenceId): void
    {
        $this->client()
            ->delete("/api/geofences/{$traccarGeofenceId}")
            ->throw();
    }

    /**
     * Same shape as linkDeviceToUser() — Traccar's /api/permissions handles
     * every kind of ownership link (user-device, device-geofence, etc.)
     * through the same endpoint, keyed by whichever pair of ids is present.
     */
    public function linkGeofenceToDevice(int $geofenceId, int $deviceId): void
    {
        $this->client()
            ->post('/api/permissions', [
                'geofenceId' => $geofenceId,
                'deviceId' => $deviceId,
            ])
            ->throw();
    }

    /**
     * Untested: this app has only ever created permissions before, never
     * removed one. Traccar's DELETE /api/permissions is documented to take
     * the same body shape as the POST — confirm during rollout.
     */
    public function unlinkGeofenceFromDevice(int $geofenceId, int $deviceId): void
    {
        $this->client()
            ->delete('/api/permissions', [
                'geofenceId' => $geofenceId,
                'deviceId' => $deviceId,
            ])
            ->throw();
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl((string) $this->baseUrl)
            ->withBasicAuth((string) $this->username, (string) $this->password)
            ->acceptJson();
    }
}
