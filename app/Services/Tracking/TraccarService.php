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
        $devices = $this->client()
            ->get('/api/devices', ['uniqueId' => $imei])
            ->throw()
            ->json();

        return $devices[0] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function latestPosition(int $deviceId): ?array
    {
        $positions = $this->client()
            ->get('/api/positions', ['deviceId' => $deviceId])
            ->throw()
            ->json();

        return $positions[0] ?? null;
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

    protected function client(): PendingRequest
    {
        return Http::baseUrl((string) $this->baseUrl)
            ->withBasicAuth((string) $this->username, (string) $this->password)
            ->acceptJson();
    }
}
