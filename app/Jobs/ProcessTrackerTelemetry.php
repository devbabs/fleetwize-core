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
                    1 => ['hardAcceleration', 'Harsh acceleration detected by accelerometer (Green Driving)'],
                    2 => ['hardBraking', 'Harsh braking detected by accelerometer (Green Driving)'],
                    3 => ['hardCornering', 'Harsh cornering detected by accelerometer (Green Driving)'],
                    default => ['greenDriving', 'Harsh driving maneuver detected'],
                };

                \App\Models\VehicleAlarm::firstOrCreate(
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

            // B. Tracker Unplugged / Main Power Cut (AVL ID 252 === 1)
            if (isset($attr['io252']) && (int) $attr['io252'] === 1) {
                \App\Models\VehicleAlarm::firstOrCreate(
                    [
                        'vehicle_id' => $vehicle->id,
                        'alarm_type' => 'powerCut',
                        'gps_time' => $gpsTime,
                    ],
                    [
                        'obd_device_id' => $deviceId,
                        'alarm_description' => 'Critical: Tracker unplugged or main vehicle power disconnected (running on backup battery).',
                    ]
                );
            }

            // C. CAN Dashboard Check Engine Light / MIL (io123 Bit 15)
            $controlFlags = isset($attr['io123']) ? (int) $attr['io123'] : 0;
            $checkEngineLightOn = (($controlFlags >> 15) & 1) === 1;

            if ($checkEngineLightOn) {
                $this->recordHealthAlertAndFault(
                    vehicle: $vehicle,
                    deviceId: $deviceId,
                    gpsTime: $gpsTime,
                    alarmType: 'checkEngineLight',
                    faultCode: 'CAN-MIL',
                    description: 'Check Engine Light (MIL) active on vehicle CAN bus. Scan ECU with an OBD-II tool for specific DTC codes.',
                    severity: 4,
                    cooldownMinutes: 360, // 6 hours
                );
            }

            // D. Engine Overheating (Coolant Temp >= 105.0 °C from io115 * 0.1)
            $coolantTemp = $state->coolant_temp
                ?? (isset($attr['io115']) ? round(((float) $attr['io115']) * 0.1, 1) : null);

            if ($coolantTemp !== null && $coolantTemp >= 105.0) {
                $this->recordHealthAlertAndFault(
                    vehicle: $vehicle,
                    deviceId: $deviceId,
                    gpsTime: $gpsTime,
                    alarmType: 'highCoolantTemp',
                    faultCode: 'CAN-TEMP',
                    description: "Critical: Engine overheating detected ({$coolantTemp} °C, threshold >= 105.0 °C).",
                    severity: 5,
                    cooldownMinutes: 30,
                );
            }

            // E. Alternator / Charging Fault (Ignition ON, RPM > 500, Voltage < 12.6V)
            $batteryVoltage = $state->battery_voltage
                ?? (isset($attr['power']) ? (float) $attr['power'] : null);
            $engineRpm = $state->engine_rpm
                ?? (isset($attr['io85']) ? (int) $attr['io85'] : null);

            if (
                $state->ignition_on === true
                && $engineRpm !== null
                && $engineRpm > 500
                && $batteryVoltage !== null
                && $batteryVoltage > 6.0
                && $batteryVoltage < 12.6
            ) {
                $this->recordHealthAlertAndFault(
                    vehicle: $vehicle,
                    deviceId: $deviceId,
                    gpsTime: $gpsTime,
                    alarmType: 'alternatorFault',
                    faultCode: 'CAN-ALT',
                    description: "Warning: Alternator not charging properly ({$batteryVoltage}V at {$engineRpm} RPM; expected >= 13.0V).",
                    severity: 4,
                    cooldownMinutes: 60,
                );
            }

            // F. Weak Vehicle Battery (Ignition OFF, Voltage < 11.8V)
            if (
                $state->ignition_on === false
                && $batteryVoltage !== null
                && $batteryVoltage > 6.0
                && $batteryVoltage < 11.8
            ) {
                $this->recordHealthAlertAndFault(
                    vehicle: $vehicle,
                    deviceId: $deviceId,
                    gpsTime: $gpsTime,
                    alarmType: 'lowBattery',
                    faultCode: 'CAN-BATT',
                    description: "Warning: Weak vehicle starter battery ({$batteryVoltage}V while engine off; threshold < 11.8V).",
                    severity: 3,
                    cooldownMinutes: 360, // 6 hours
                );
            }
        }

        protected function recordHealthAlertAndFault(
            Vehicle $vehicle,
            ?string $deviceId,
            string $gpsTime,
            string $alarmType,
            string $faultCode,
            string $description,
            int $severity,
            int $cooldownMinutes,
        ): void {
            $recentAlarmExists = \App\Models\VehicleAlarm::query()
                ->where('vehicle_id', $vehicle->id)
                ->where('alarm_type', $alarmType)
                ->where('created_at', '>=', now()->subMinutes($cooldownMinutes))
                ->exists();

            if (! $recentAlarmExists) {
                \App\Models\VehicleAlarm::create([
                    'vehicle_id' => $vehicle->id,
                    'obd_device_id' => $deviceId,
                    'alarm_type' => $alarmType,
                    'alarm_description' => $description,
                    'gps_time' => $gpsTime,
                ]);
            }

            $recentFaultExists = \App\Models\VehicleFault::query()
                ->where('vehicle_id', $vehicle->id)
                ->where('obd_code', $faultCode)
                ->where('created_at', '>=', now()->subMinutes($cooldownMinutes))
                ->exists();

            if (! $recentFaultExists) {
                \App\Models\VehicleFault::create([
                    'vehicle_id' => $vehicle->id,
                    'obd_device_id' => $deviceId,
                    'obd_code' => $faultCode,
                    'obd_description' => $description,
                    'meaning' => $description,
                    'severity' => $severity,
                    'log_time' => $gpsTime,
                ]);
            }
        }
    }
