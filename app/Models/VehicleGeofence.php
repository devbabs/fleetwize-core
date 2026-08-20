<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $vehicle_id
 * @property int $geofence_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Vehicle|null $vehicle
 * @property-read Geofence $geofence
 *
 * @mixin \Eloquent
 */
class VehicleGeofence extends Model
{
    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<Geofence, $this>
     */
    public function geofence(): BelongsTo
    {
        return $this->belongsTo(Geofence::class);
    }
}
