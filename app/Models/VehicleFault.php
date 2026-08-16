<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $vehicle_id
 * @property string|null $obd_device_id
 * @property string|null $obd_code
 * @property string|null $obd_description
 * @property string|null $obd_description_en
 * @property CarbonImmutable|null $log_time
 * @property CarbonImmutable|null $cleared_at
 * @property string|null $meaning
 * @property array<array-key, mixed>|null $common_causes
 * @property array<array-key, mixed>|null $symptoms
 * @property array<array-key, mixed>|null $possible_fixes
 * @property string|null $note
 * @property int|null $severity
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Vehicle|null $vehicle
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFault newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFault newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFault query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFault whereClearedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFault whereCommonCauses($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFault whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFault whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFault whereLogTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFault whereMeaning($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFault whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFault whereObdCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFault whereObdDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFault whereObdDescriptionEn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFault whereObdDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFault wherePossibleFixes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFault whereSeverity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFault whereSymptoms($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFault whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleFault whereVehicleId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'obd_device_id', 'obd_code', 'obd_description', 'obd_description_en', 'log_time', 'cleared_at',
    'meaning', 'common_causes', 'symptoms', 'possible_fixes', 'note', 'severity',
])]
class VehicleFault extends Model
{
    protected function casts(): array
    {
        return [
            'log_time' => 'datetime',
            'cleared_at' => 'datetime',
            'common_causes' => 'array',
            'symptoms' => 'array',
            'possible_fixes' => 'array',
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
