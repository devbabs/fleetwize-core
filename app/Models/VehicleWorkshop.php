<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $vehicle_id
 * @property int $workshop_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Vehicle|null $vehicle
 * @property-read Workshop $workshop
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleWorkshop newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleWorkshop newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleWorkshop query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleWorkshop whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleWorkshop whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleWorkshop whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleWorkshop whereVehicleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleWorkshop whereWorkshopId($value)
 *
 * @mixin \Eloquent
 */
class VehicleWorkshop extends Model
{
    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<Workshop, $this>
     */
    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }
}
