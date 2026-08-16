<?php

namespace App\Services\Tracking;

use App\Models\Vehicle;
use App\Models\VehicleTrackerState;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

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

    protected function client(): PendingRequest
    {
        return Http::baseUrl((string) $this->baseUrl)
            ->withBasicAuth((string) $this->username, (string) $this->password)
            ->acceptJson();
    }
}
