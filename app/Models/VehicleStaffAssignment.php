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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleStaffAssignment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleStaffAssignment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleStaffAssignment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleStaffAssignment whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleStaffAssignment whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleStaffAssignment whereCompanyUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleStaffAssignment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleStaffAssignment whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleStaffAssignment whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleStaffAssignment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleStaffAssignment whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleStaffAssignment whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleStaffAssignment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleStaffAssignment whereVehicleId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['start_date', 'end_date', 'start_time', 'end_time', 'comment'])]
class VehicleStaffAssignment extends Model
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
