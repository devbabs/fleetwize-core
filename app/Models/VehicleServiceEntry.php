<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $company_id
 * @property int $vehicle_id
 * @property int|null $vendor_id
 * @property float|null $meter_reading
 * @property CarbonImmutable|null $starts_at
 * @property CarbonImmutable|null $ends_at
 * @property string|null $reference
 * @property string|null $comments
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Company $company
 * @property-read Collection<int, ServiceEntryIssue> $issues
 * @property-read int|null $issues_count
 * @property-read Collection<int, ServiceEntryTask> $tasks
 * @property-read int|null $tasks_count
 * @property-read Vehicle|null $vehicle
 * @property-read Vendor|null $vendor
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleServiceEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleServiceEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleServiceEntry query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleServiceEntry whereComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleServiceEntry whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleServiceEntry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleServiceEntry whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleServiceEntry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleServiceEntry whereMeterReading($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleServiceEntry whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleServiceEntry whereStartsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleServiceEntry whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleServiceEntry whereVehicleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleServiceEntry whereVendorId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['meter_reading', 'starts_at', 'ends_at', 'reference', 'comments'])]
class VehicleServiceEntry extends Model
{
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
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

    /**
     * @return HasMany<ServiceEntryTask, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(ServiceEntryTask::class);
    }

    /**
     * @return HasMany<ServiceEntryIssue, $this>
     */
    public function issues(): HasMany
    {
        return $this->hasMany(ServiceEntryIssue::class);
    }
}
