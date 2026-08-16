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
 * @property string|null $reference
 * @property int $vehicle_id
 * @property int $workshop_id
 * @property int $created_by_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $createdBy
 * @property-read Collection<int, VehicleDiagnosticReportFault> $faults
 * @property-read int|null $faults_count
 * @property-read Vehicle|null $vehicle
 * @property-read Workshop $workshop
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDiagnosticReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDiagnosticReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDiagnosticReport query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDiagnosticReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDiagnosticReport whereCreatedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDiagnosticReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDiagnosticReport whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDiagnosticReport whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDiagnosticReport whereVehicleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDiagnosticReport whereWorkshopId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['reference'])]
class VehicleDiagnosticReport extends Model
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * @return HasMany<VehicleDiagnosticReportFault, $this>
     */
    public function faults(): HasMany
    {
        return $this->hasMany(VehicleDiagnosticReportFault::class);
    }
}
