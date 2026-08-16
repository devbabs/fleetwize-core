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
}
