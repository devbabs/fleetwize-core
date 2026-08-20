<?php

namespace App\Support;

/**
 * Builds the WKT `area` strings Traccar's /api/geofences endpoint expects.
 * Isolated in one place because the exact coordinate order (Traccar uses
 * lat-first, unlike GeoJSON's lon-first convention) was unconfirmed at
 * write time — verify against a geofence created through Traccar's own
 * web UI (inspect `tc_geofences.area` directly) before relying on this,
 * and fix the order here in one spot if it turns out to be wrong.
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
