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
 * @property int $company_user_id
 * @property CarbonImmutable|null $start_date
 * @property CarbonImmutable|null $end_date
 * @property string|null $start_time
 * @property string|null $end_time
 * @property string|null $comment
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Company $company
 * @property-read CompanyUser $companyUser
 * @property-read Vehicle|null $vehicle
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAssignment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAssignment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAssignment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAssignment whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAssignment whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAssignment whereCompanyUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAssignment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAssignment whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAssignment whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAssignment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAssignment whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAssignment whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAssignment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAssignment whereVehicleId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['start_date', 'end_date', 'start_time', 'end_time', 'comment'])]
class VehicleAssignment extends Model
{
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<CompanyUser, $this>
     */
    public function companyUser(): BelongsTo
    {
        return $this->belongsTo(CompanyUser::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
