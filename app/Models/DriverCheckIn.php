<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $company_user_id
 * @property string|null $tyre
 * @property string|null $vehicle_condition
 * @property string|null $engine_oil
 * @property string|null $water_level
 * @property string|null $image
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read CompanyUser $companyUser
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverCheckIn newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverCheckIn newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverCheckIn query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverCheckIn whereCompanyUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverCheckIn whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverCheckIn whereEngineOil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverCheckIn whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverCheckIn whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverCheckIn whereTyre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverCheckIn whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverCheckIn whereVehicleCondition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverCheckIn whereWaterLevel($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['tyre', 'vehicle_condition', 'engine_oil', 'water_level', 'image'])]
class DriverCheckIn extends Model
{
    /**
     * @return BelongsTo<CompanyUser, $this>
     */
    public function companyUser(): BelongsTo
    {
        return $this->belongsTo(CompanyUser::class);
    }
}
