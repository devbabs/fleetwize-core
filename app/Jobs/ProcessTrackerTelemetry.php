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
            $this->provisionTeltonikaIfNeeded($traccar);

            $vehicle = Vehicle::query()->where('obd_device_imei', $imei)->first();

            if (! $vehicle) {
                Log::warning("Tracker telemetry received for an IMEI with no matching vehicle: {$imei}");

                return;
            }

            // --- REPLACE STARTS HERE ---
            // 1. Fetch current stored odometer to protect against sentinels / drops
            $lastKnownKm = $vehicle->trackerState?->odometer;

            // 2. Normalize incoming telemetry (converts meters -> km, knots -> km/h, etc.)
            $normalized = TraccarPayloadNormalizer::normalize($this->position, $this->device);

            // 3. Fall back to last known reading if raw odometer was invalid, sentinel, or absent
            if ($normalized['odometer'] === null && $lastKnownKm !== null) {
                $normalized['odometer'] = $lastKnownKm;
            }

            // 4. Upsert the state record
            $trackerState = VehicleTrackerState::query()->updateOrCreate(
                ['vehicle_id' => $vehicle->id],
                $normalized,
            );
            // --- REPLACE ENDS HERE ---

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

        /**
     * Automatically push Green Driving (io253), OBD/CAN Fault Code reading (io30/io281),
     * Unplug Detection (io252), and Excessive Idling (io251) parameters once per
     * Teltonika tracker so new installs don't require manual curl/SMS setup.
     */
        protected function provisionTeltonikaIfNeeded(TraccarService $traccar): void
        {
            $deviceId = $this->device['id'] ?? null;
            $protocol = $this->position['protocol'] ?? null;

            if (! $deviceId || $protocol !== 'teltonika') {
                return;
            }

            $cacheKey = "traccar-teltonika-provisioned:{$deviceId}";

            if (Cache::has($cacheKey)) {
                return;
            }

            try {
                $traccar->sendCommand(
                    (int) $deviceId,
                    'setparam 11000:1;11004:25;11005:28;11006:30;11007:0;40100:1;40430:1;11500:2;11200:1',
                    'custom'
                );

                Cache::forever($cacheKey, true);
                Log::info("Auto-provisioned Teltonika device {$deviceId} over TCP.");
            } catch (Throwable $e) {
                Log::warning("Failed to auto-provision Teltonika device {$deviceId}.", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
