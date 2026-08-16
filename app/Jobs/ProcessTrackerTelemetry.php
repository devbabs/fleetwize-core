<?php

namespace App\Jobs;

use App\Models\Vehicle;
use App\Models\VehicleTrackerState;
use App\Services\Tracking\TraccarPayloadNormalizer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessTrackerTelemetry implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $position
     * @param  array<string, mixed>  $device
     */
    public function __construct(
        public array $position,
        public array $device,
    ) {}

    public function handle(): void
    {
        $imei = TraccarPayloadNormalizer::imei($this->device);

        if (! $imei) {
            Log::warning('Tracker telemetry received with no device uniqueId (IMEI).', ['device' => $this->device]);

            return;
        }

        $vehicle = Vehicle::query()->where('obd_device_imei', $imei)->first();

        if (! $vehicle) {
            Log::warning("Tracker telemetry received for an IMEI with no matching vehicle: {$imei}");

            return;
        }

        VehicleTrackerState::query()->updateOrCreate(
            ['vehicle_id' => $vehicle->id],
            TraccarPayloadNormalizer::normalize($this->position, $this->device),
        );
    }
}
