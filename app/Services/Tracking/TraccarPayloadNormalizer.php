<?php

namespace App\Services\Tracking;

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

        return [
            'latitude' => $position['latitude'] ?? null,
            'longitude' => $position['longitude'] ?? null,
            'speed' => isset($position['speed']) ? round($position['speed'] * 1.852, 2) : null, // knots -> km/h
            'heading' => $position['course'] ?? null,
            'ignition_on' => $attributes['ignition'] ?? null,
            'battery_voltage' => $attributes['battery'] ?? $attributes['power'] ?? null,
            'fuel_level' => $attributes['fuel'] ?? null,
            'reported_at' => $position['fixTime'] ?? null,
            'raw_payload' => ['position' => $position, 'device' => $device],
            'engine_rpm' => $attributes['rpm'] ?? null,
            'engine_load' => $attributes['engineLoad'] ?? null,
            'obd_speed' => $attributes['obdSpeed'] ?? null, // OBD PID 0x0D, already km/h — no conversion needed
            'is_moving' => $attributes['motion'] ?? null,
            'battery_level' => $attributes['batteryLevel'] ?? null,
            'satellite_count' => $attributes['sat'] ?? null,
            'signal_strength' => $attributes['rssi'] ?? null,
            
            // Normalized engine runtime (milliseconds/seconds -> decimal hours)
            'engine_hours' => self::normalizeEngineHours($attributes['hours'] ?? null),
            
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
                isset($attributes['odometer']) ? (float) $attributes['odometer'] : null
            ),
            'obd_odometer' => self::normalizeOdometer(
                isset($attributes['obdOdometer']) ? (float) $attributes['obdOdometer'] : null
            ),
            'total_distance' => self::normalizeOdometer(
                isset($attributes['totalDistance']) ? (float) $attributes['totalDistance'] : null
            ),

            'hard_cornering_count' => $attributes['hardCorneringCount'] ?? null,
            'hard_acceleration_count' => $attributes['hardAccelerationCount'] ?? null,
            'hard_deceleration_count' => $attributes['hardDecelerationCount'] ?? null,
        ];
    }

    /**
     * Normalize and sanitize incoming odometer data from Traccar.
     *
     * @param float|null $rawMeters Value from attributes.odometer or obdOdometer (meters)
     * @param float|null $lastKnownKm Fallback to previous valid reading
     * @return float|null Sanitized value in Kilometers
     */
    public static function normalizeOdometer(?float $rawMeters, ?float $lastKnownKm = null): ?float
    {
        if ($rawMeters === null) {
            return $lastKnownKm;
        }

        // 1. Guard against 32-bit unsigned integer sentinel errors (>= 1,000,000,000)
        // 0xFFFFFFFF = 4,294,967,295 (sentinel emitted when CAN bus / ECU register is offline)
        if ($rawMeters >= 1_000_000_000) {
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
     * Traccar typically sends milliseconds (e.g. 1393029000 ms) or seconds.
     *
     * @param float|int|null $rawTime
     * @return float|null Engine hours rounded to 1 decimal place
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

        return is_string($uniqueId) && $uniqueId !== '' ? $uniqueId : null;
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
}
