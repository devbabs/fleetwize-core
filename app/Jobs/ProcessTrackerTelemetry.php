<?php

    namespace App\Jobs;

    use App\Events\VehicleTrackerStateUpdated;
    use App\Models\Vehicle;
    use App\Models\VehicleTrackerState;
    use App\Services\Tracking\TraccarPayloadNormalizer;
    use App\Services\Tracking\TraccarService;
    use Illuminate\Contracts\Queue\ShouldQueue;
    use Illuminate\Foundation\Queue\Queueable;
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Facades\Log;
    use Throwable;

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

        public function handle(TraccarService $traccar): void
        {
            $imei = TraccarPayloadNormalizer::imei($this->device);

            if (! $imei) {
                Log::warning('Tracker telemetry received with no device uniqueId (IMEI).', ['device' => $this->device]);

                return;
            }

            $this->linkDeviceIfNeeded($traccar);

            $vehicle = Vehicle::query()->where('obd_device_imei', $imei)->first();

            if (! $vehicle) {
                Log::warning("Tracker telemetry received for an IMEI with no matching vehicle: {$imei}");

                return;
            }

            $trackerState = VehicleTrackerState::query()->updateOrCreate(
                ['vehicle_id' => $vehicle->id],
                TraccarPayloadNormalizer::normalize($this->position, $this->device),
            );

            try {
                broadcast(new VehicleTrackerStateUpdated($vehicle, $trackerState));
            } catch (Throwable $e) {
                // The state write above already succeeded — a broadcast hiccup
                // shouldn't fail/retry the whole ingestion job, it just means
                // this one tick doesn't push live; the next report resyncs it.
                Log::warning('Failed to broadcast vehicle tracker state update.', [
                    'vehicle_id' => $vehicle->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        /**
         * Devices auto-registered via Traccar's database.registerUnknown have no
         * owner and stay invisible on the map/API until linked to a user — do
         * that once per device, the first time telemetry arrives for it, rather
         * than requiring a manual step per new tracker.
         */
        protected function linkDeviceIfNeeded(TraccarService $traccar): void
        {
            $deviceId = $this->device['id'] ?? null;

            if (! $deviceId) {
                return;
            }

            $cacheKey = "traccar-device-linked:{$deviceId}";

            if (Cache::has($cacheKey)) {
                return;
            }

            try {
                $traccar->linkDeviceToUser((int) $deviceId);
                Cache::forever($cacheKey, true);
            } catch (Throwable $e) {
                Log::warning("Failed to auto-link Traccar device {$deviceId} to owner user.", ['error' => $e->getMessage()]);
            }
        }
    }
