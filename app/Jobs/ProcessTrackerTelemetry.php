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

            $vehicle = Vehicle::query()
                ->with('trackerState')
                ->where('obd_device_imei', $imei)
                ->first();

            if (! $vehicle) {
                Log::warning("Tracker telemetry received for an IMEI with no matching vehicle: {$imei}");

                return;
            }

            $existingState = $vehicle->trackerState;

            // 1. Normalize incoming telemetry (converts meters -> km, knots -> km/h, Teltonika io* -> columns)
            $normalized = TraccarPayloadNormalizer::normalize($this->position, $this->device);

            // 2. Anti-Flicker Guard: Preserve last known cumulative/sensor readings if a heartbeat packet omits them
            $stickyColumns = [
                'odometer',
                'obd_odometer',
                'total_distance',
                'fuel_level',
                'fuel_level_percent',
                'fuel_consumed',
                'engine_hours',
                'battery_voltage',
                'tracker_battery_voltage',
                'coolant_temp',
            ];

            if ($existingState) {
                foreach ($stickyColumns as $column) {
                    if (($normalized[$column] ?? null) === null && $existingState->{$column} !== null) {
                        // Guard against perpetuating legacy 32-bit sentinel values (>= 1,500,000 km)
                        if (in_array($column, ['odometer', 'obd_odometer'], true) && $existingState->{$column} >= 1_500_000) {
                            continue;
                        }

                        $normalized[$column] = $existingState->{$column};
                    }
                }
            }

            // 3. Upsert the state record
            $trackerState = VehicleTrackerState::query()->updateOrCreate(
                ['vehicle_id' => $vehicle->id],
                $normalized,
            );

            // 4. Evaluate Teltonika / OBD diagnostic & Green Driving alerts from position attributes
            $this->evaluatePositionDiagnostics($vehicle, $trackerState);

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
                    'setparam 101:4;104:30000;105:13200;11000:1;11004:25;11005:28;11006:30;11007:0;40100:1;40430:1;11500:2;11200:1',
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

        protected function evaluatePositionDiagnostics(Vehicle $vehicle, VehicleTrackerState $state): void
        {
            $attr = $this->position['attributes'] ?? [];
            $gpsTime = $this->position['fixTime'] ?? now()->toIso8601String();
            $deviceId = isset($this->device['id']) ? (string) $this->device['id'] : null;

            // A. Teltonika Green Driving Event (AVL ID 253: 1=Harsh Accel, 2=Harsh Brake, 3=Harsh Cornering)
            if (isset($attr['io253']) && (int) $attr['io253'] > 0) {
                [$type, $desc] = match ((int) $attr['io253']) {
                    1 => ['hardAcceleration', 'Driver Behavior: Harsh acceleration detected (Green Driving)'],
                    2 => ['hardBraking', 'Driver Behavior: Harsh braking detected (Green Driving)'],
                    3 => ['hardCornering', 'Driver Behavior: Harsh cornering detected (Green Driving)'],
                    default => ['greenDriving', 'Driver Behavior: Harsh driving maneuver detected'],
                };

                VehicleAlarm::firstOrCreate(
                    [
                        'vehicle_id' => $vehicle->id,
                        'alarm_type' => $type,
                        'gps_time' => $gpsTime,
                    ],
                    [
                        'obd_device_id' => $deviceId,
                        'alarm_description' => $desc,
                    ]
                );
            }

            // B. Teltonika Unplug / Power Disconnect Detection (AVL ID 252 == 1)
            if (isset($attr['io252']) && (int) $attr['io252'] === 1) {
                VehicleAlarm::firstOrCreate(
                    [
                        'vehicle_id' => $vehicle->id,
                        'alarm_type' => 'powerCut',
                        'gps_time' => $gpsTime,
                    ],
                    [
                        'obd_device_id' => $deviceId,
                        'alarm_description' => 'Critical: Tracker unplugged or main vehicle power disconnected (running on backup battery)',
                    ]
                );
            }

            // C. Engine Overheating Alert (Coolant Temp >= 105 °C)
            if ($state->coolant_temp !== null && $state->coolant_temp >= 105.0) {
                VehicleAlarm::firstOrCreate(
                    [
                        'vehicle_id' => $vehicle->id,
                        'alarm_type' => 'highCoolantTemp',
                        'gps_time' => $gpsTime,
                    ],
                    [
                        'obd_device_id' => $deviceId,
                        'alarm_description' => "Critical: Engine overheating detected ({$state->coolant_temp} °C)",
                    ]
                );
            }

            // D. Low Vehicle Battery Alert (< 11.8V) — debounced once per 6 hours per vehicle
            if ($state->battery_voltage !== null && $state->battery_voltage > 6.0 && $state->battery_voltage < 11.8) {
                $recentBatteryAlarm = VehicleAlarm::where('vehicle_id', $vehicle->id)
                    ->where('alarm_type', 'lowBattery')
                    ->where('created_at', '>=', now()->subHours(6))
                    ->exists();

                if (! $recentBatteryAlarm) {
                    VehicleAlarm::create([
                        'vehicle_id' => $vehicle->id,
                        'obd_device_id' => $deviceId,
                        'alarm_type' => 'lowBattery',
                        'alarm_description' => "Warning: Low vehicle battery voltage ({$state->battery_voltage}V)",
                        'gps_time' => $gpsTime,
                    ]);
                }
            }
        }
    }
