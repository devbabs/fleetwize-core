<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $company_id
 * @property int $vehicle_id
 * @property int|null $vendor_id
 * @property float|null $meter_reading
 * @property CarbonImmutable $date
 * @property float $litres
 * @property numeric $price_per_litre
 * @property string|null $reference
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Company $company
 * @property-read Vehicle|null $vehicle
 * @property-read Vendor|null $vendor
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFuelEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFuelEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFuelEntry query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFuelEntry whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFuelEntry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFuelEntry whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFuelEntry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFuelEntry whereLitres($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFuelEntry whereMeterReading($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFuelEntry wherePricePerLitre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFuelEntry whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFuelEntry whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFuelEntry whereVehicleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFuelEntry whereVendorId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['meter_reading', 'date', 'litres', 'price_per_litre', 'reference'])]
class VehicleFuelEntry extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
