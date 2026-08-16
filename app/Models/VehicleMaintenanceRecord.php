<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $vehicle_id
 * @property CarbonImmutable $maintained_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Vehicle|null $vehicle
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleMaintenanceRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleMaintenanceRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleMaintenanceRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleMaintenanceRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleMaintenanceRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleMaintenanceRecord whereMaintainedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleMaintenanceRecord whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleMaintenanceRecord whereVehicleId($value)
 *
 * @mixin \Eloquent
 */
class VehicleMaintenanceRecord extends Model
{
    protected function casts(): array
    {
        return [
            'maintained_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
