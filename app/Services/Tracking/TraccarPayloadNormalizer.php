<?php

namespace App\Services\Tracking;

use App\Models\Vehicle;

class TraccarPayloadNormalizer
{
    /**
     * Map a Traccar "position" + "device" payload (identical shape whether it
     * arrives via the forwarding webhook or a REST API pull) to the columns
     * on VehicleTrackerState.
     *
     * @param  array<string, mixed>  $position
     * @param  array<string, mixed>  $device
     * @return array<string, mixed>
     */
public static function normalize(array $position, array $device): array
    {
        $attributes = $position['attributes'] ?? [];

        // Prioritize CAN dashboard mileage (Teltonika io87 or JT808 obdOdometer)
        // over the relative GPS trip counter (attributes.odometer)
        $rawPrimaryOdometer = $attributes['io87']
            ?? $attributes['obdOdometer']
            ?? $attributes['odometer']
            ?? null;

        $rawObdOdometer = $attributes['obdOdometer']
            ?? $attributes['io87']
            ?? null;

        $speedKmh = isset($position['speed']) ? round($position['speed'] * 1.852, 2) : null; // knots -> km/h
        $obdSpeedKmh = $attributes['obdSpeed'] ?? $attributes['io81'] ?? null;
        $powerVoltage = isset($attributes['power']) ? (float) $attributes['power'] : null;

        $rawIgnition = $attributes['ignition']
            ?? (isset($attributes['io1']) ? (bool) $attributes['io1'] : null);

        $rawMotion = $attributes['motion'] ?? null;

        // Guard against constant-12V DIN1 miswiring on 12V vehicles:
        // If GPS speed is 0, CAN speed is 0, distance is 0, and external battery voltage
        // is resting (< 13.0V, meaning alternator is OFF), the engine is physically OFF.
        if (
            $rawIgnition === true
            && ($speedKmh === null || $speedKmh < 1.0)
            && ($obdSpeedKmh === null || (float) $obdSpeedKmh < 1.0)
            && $powerVoltage !== null
            && $powerVoltage > 8.0
            && $powerVoltage < 13.0
            && ((float) ($attributes['distance'] ?? 0)) === 0.0
        ) {
            $rawIgnition = false;
            $rawMotion = false;
        }

        // When ignition is OFF and speed is 0, force motion to false and zero out
        // stale CAN bus RPM / load registers cached by the FMC150 after engine shutoff.
        if ($rawIgnition === false && ($speedKmh === null || $speedKmh < 1.0)) {
            $rawMotion = false;
        }

        $engineRpm = $rawIgnition === false
            ? 0
            : ($attributes['rpm'] ?? $attributes['io85'] ?? null);

        $engineLoad = $rawIgnition === false
            ? 0.0
            : ($attributes['engineLoad'] ?? $attributes['io82'] ?? null);

        return [
            'latitude' => $position['latitude'] ?? null,
            'longitude' => $position['longitude'] ?? null,
            'speed' => $speedKmh,
            'heading' => $position['course'] ?? null,
            'ignition_on' => $rawIgnition,

            // External vehicle battery / alternator voltage (Teltonika 'power' or AVL ID 66 in mV)
            'battery_voltage' => isset($attributes['power'])
                ? round((float) $attributes['power'], 2)
                : (isset($attributes['io66'])
                    ? round(((float) $attributes['io66']) / 1000.0, 2)
                    : (isset($attributes['battery']) ? round((float) $attributes['battery'], 2) : null)),

            // Internal tracker backup battery voltage (Teltonika 'battery': ~4.0V Li-ion)
            'tracker_battery_voltage' => isset($attributes['battery'])
                ? round((float) $attributes['battery'], 3)
                : null,

            // Fuel volume in Liters: JT808 'fuel' or Teltonika CAN 'io84' (0.1 L multiplier: 201 -> 20.1 L)
            'fuel_level' => $attributes['fuel']
                ?? (isset($attributes['io84']) ? round(((float) $attributes['io84']) * 0.1, 1) : null),

            // Fuel tank level in Percent (%): Teltonika CAN 'io89' (e.g. 25 -> 25%)
            'fuel_level_percent' => isset($attributes['io89'])
                ? (int) $attributes['io89']
                : null,

            // Cumulative fuel consumed in Liters: Traccar 'spentFuel', Teltonika 'io107' (0.1 L multiplier), or 'io83'
            'fuel_consumed' => $attributes['spentFuel']
                ?? (isset($attributes['io107']) ? round(((float) $attributes['io107']) * 0.1, 1) : null)
                ?? (isset($attributes['io83']) ? round(((float) $attributes['io83']) * 0.1, 1) : null),

            'reported_at' => $position['fixTime'] ?? null,
            'raw_payload' => ['position' => $position, 'device' => $device],

            // Engine RPM: JT808 'rpm' or Teltonika CAN 'io85' (0 when ignition is off)
            'engine_rpm' => $engineRpm,

            // Engine / Throttle Load (%): JT808 'engineLoad' or Teltonika CAN 'io82' (0 when ignition is off)
            'engine_load' => $engineLoad,

            // Engine Coolant Temperature (°C): JT808 'coolantTemp' or Teltonika CAN 'io115' (0.1 °C multiplier: 860 -> 86.0 °C)
            'coolant_temp' => $attributes['coolantTemp']
                ?? (isset($attributes['io115']) ? round(((float) $attributes['io115']) * 0.1, 1) : null),

            // Wheel / OBD Speed (km/h): JT808 'obdSpeed' or Teltonika CAN 'io81'
            'obd_speed' => $obdSpeedKmh,

            'is_moving' => $rawMotion,
            'battery_level' => $attributes['batteryLevel'] ?? null,
            'satellite_count' => $attributes['sat'] ?? null,
            'signal_strength' => $attributes['rssi'] ?? null,

            // Normalized engine runtime (Traccar 'hours' in ms/s, or Teltonika CAN 'io103' in minutes)
            'engine_hours' => self::normalizeEngineHours($attributes['hours'] ?? null)
                ?? (isset($attributes['io103']) ? round(((float) $attributes['io103']) / 60.0, 1) : null),

            'is_blocked' => $attributes['blocked'] ?? null,
            'is_charging' => $attributes['charge'] ?? null,

            'unique_id' => $device['uniqueId'] ?? null,
            'device_status' => $device['status'] ?? null,
            'protocol' => $position['protocol'] ?? null,
            'altitude' => $position['altitude'] ?? null,
            'gps_valid' => $position['valid'] ?? null,
            'device_time' => $position['deviceTime'] ?? null,
            'server_time' => $position['serverTime'] ?? null,

            // Normalized distance metrics (meters -> km + 32-bit sentinel guard)
            'odometer' => self::normalizeOdometer(
                $rawPrimaryOdometer !== null ? (float) $rawPrimaryOdometer : null
            ),
            'obd_odometer' => self::normalizeOdometer(
                $rawObdOdometer !== null ? (float) $rawObdOdometer : null
            ),
            'total_distance' => self::normalizeOdometer(
                isset($attributes['totalDistance']) ? (float) $attributes['totalDistance'] : null
            ),

            'hard_cornering_count' => $attributes['hardCorneringCount'] ?? null,
            'hard_acceleration_count' => $attributes['hardAccelerationCount'] ?? null,
            'hard_deceleration_count' => $attributes['hardDecelerationCount'] ?? null,

            // Active OBD-II / CAN Fault Code Count (Teltonika AVL ID 30) & Fault Code String (AVL ID 281 / dtcs)
            'dtc_count' => isset($attributes['io30']) ? (int) $attributes['io30'] : null,
            'dtc_codes' => $attributes['dtcs']
                ?? $attributes['faultCodes']
                ?? (isset($attributes['io281']) ? (string) $attributes['io281'] : null),
        ];
    }

    /**
     * Normalize and sanitize incoming odometer data from Traccar.
     *
     * @param  float|null  $rawMeters  Value from attributes.io87, odometer, or obdOdometer (meters)
     * @param  float|null  $lastKnownKm  Fallback to previous valid reading
     * @return float|null  Sanitized value in Kilometers
     */
    public static function normalizeOdometer(?float $rawMeters, ?float $lastKnownKm = null): ?float
    {
        if ($rawMeters === null) {
            return $lastKnownKm;
        }

        // 1. Guard against 32-bit unsigned integer sentinel errors (>= 1,000,000,000)
        // 0xFFFFFFFF = 4,294,967,295 (sentinel emitted when CAN bus / ECU register is offline)
        if ($rawMeters >= 1_000_000_000 || $rawMeters < 0) {
            return $lastKnownKm;
        }

        // 2. Convert meters to kilometers
        $odometerKm = round($rawMeters / 1000.0, 1);

        // 3. Guard against impossible vehicle mileage (> 1,500,000 km)
        if ($odometerKm > 1_500_000) {
            return $lastKnownKm;
        }

        return $odometerKm;
    }

    /**
     * Normalize Traccar engine runtime into hours.
     * Traccar typically sends milliseconds (e.g. 200886000 ms) or seconds.
     *
     * @param  float|int|null  $rawTime
     * @return float|null  Engine hours rounded to 1 decimal place
     */
    public static function normalizeEngineHours(float|int|null $rawTime): ?float
    {
        if ($rawTime === null || $rawTime <= 0) {
            return null;
        }

        // If >= 100,000,000, it is guaranteed to be in milliseconds
        if ($rawTime >= 100_000_000) {
            return round($rawTime / 3_600_000.0, 1);
        }

        // Otherwise treated as seconds
        return round($rawTime / 3600.0, 1);
    }

    /**
     * @param  array<string, mixed>  $device
     */
    public static function imei(array $device): ?string
    {
        $uniqueId = $device['uniqueId'] ?? null;

        return is_string($uniqueId) && $uniqueId !== '' ? trim($uniqueId) : null;
    }

    /**
     * Map a Traccar "event" + "device" payload to the columns on VehicleAlarm.
     *
     * @param  array<string, mixed>  $event
     * @param  array<string, mixed>  $device
     * @return array<string, mixed>
     */
    public static function normalizeEvent(array $event, array $device): array
    {
        $attributes = $event['attributes'] ?? [];
        $type = $event['type'] ?? null;

        return [
            'obd_device_id' => isset($device['id']) ? (string) $device['id'] : null,
            'alarm_id' => isset($event['id']) ? (string) $event['id'] : null,
            'alarm_type' => $type,
            'alarm_description' => $attributes['alarm'] ?? $type,
            'gps_time' => $event['eventTime'] ?? null,
        ];
    }

    public static function sanitizeOdometer(mixed $rawMeters): ?int
    {
        if ($rawMeters === null) {
            return null;
        }

        $meters = (float) $rawMeters;

        // Guard against 32-bit unsigned integer error sentinels (e.g. 4294967200)
        if ($meters >= 1_000_000_000 || $meters < 0) {
            return null;
        }

        // Convert meters to kilometers
        $km = (int) round($meters / 1000.0);

        // Guard against impossible vehicle mileage (> 1,500,000 km)
        if ($km > 1_500_000) {
            return null;
        }

        return $km;
    }
}
