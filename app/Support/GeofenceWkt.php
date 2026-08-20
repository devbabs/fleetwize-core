<?php

namespace App\Support;

/**
 * Builds the WKT `area` strings Traccar's /api/geofences endpoint expects.
 * Coordinate order is lat-first (unlike GeoJSON's lon-first convention) —
 * confirmed by inspecting `tc_geofences.area` for a geofence created
 * directly through Traccar's own web UI.
 */
class GeofenceWkt
{
    public static function circle(float $latitude, float $longitude, float $radiusMeters): string
    {
        return "CIRCLE ({$latitude} {$longitude}, {$radiusMeters})";
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $points  [lat, lng] pairs
     */
    public static function polygon(array $points): string
    {
        $closed = $points;

        if ($closed !== [] && $closed[0] !== $closed[count($closed) - 1]) {
            $closed[] = $closed[0];
        }

        $pairs = array_map(fn (array $point) => "{$point[0]} {$point[1]}", $closed);

        return 'POLYGON (('.implode(', ', $pairs).'))';
    }
}
