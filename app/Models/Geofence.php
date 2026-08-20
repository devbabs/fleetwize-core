<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $shape
 * @property numeric|null $center_latitude
 * @property numeric|null $center_longitude
 * @property float|null $radius_meters
 * @property array<int, array{0: float, 1: float}>|null $polygon
 * @property int|null $traccar_geofence_id
 * @property string|null $color
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Vehicle> $vehicles
 *
 * @mixin \Eloquent
 */
#[Fillable(['company_id', 'name', 'shape', 'center_latitude', 'center_longitude', 'radius_meters', 'polygon', 'traccar_geofence_id', 'color'])]
class Geofence extends Model
{
    protected function casts(): array
    {
        return [
            'center_latitude' => 'float',
            'center_longitude' => 'float',
            'radius_meters' => 'float',
            'polygon' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsToMany<Vehicle, $this>
     */
    public function vehicles(): BelongsToMany
    {
        return $this->belongsToMany(Vehicle::class, 'vehicle_geofences');
    }
}
