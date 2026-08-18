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
            'engine_hours' => $attributes['hours'] ?? null,
            'is_blocked' => $attributes['blocked'] ?? null,
            'is_charging' => $attributes['charge'] ?? null,
        ];
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
     * Map a Traccar "event" + "device" payload (from event.forward, a
     * separate mechanism from position forward) to the columns on
     * VehicleAlarm. Field names follow Traccar's documented Event model
     * (id, type, eventTime, attributes) — unlike normalize() above, this
     * hasn't yet been checked against a real captured payload; verify
     * during rollout (temporary logging in the webhook controller) and
     * correct here if any field turns out to be named/nested differently.
     * Traccar events reference a positionId rather than carrying lat/lng
     * inline, so those are left null unless a real payload shows otherwise.
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
