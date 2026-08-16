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
 * @property string|null $alarm_id
 * @property string|null $alarm_type
 * @property string|null $alarm_description
 * @property string|null $description
 * @property numeric|null $latitude
 * @property numeric|null $longitude
 * @property CarbonImmutable|null $gps_time
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Vehicle|null $vehicle
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAlarm newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAlarm newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAlarm query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAlarm whereAlarmDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAlarm whereAlarmId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAlarm whereAlarmType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAlarm whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAlarm whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAlarm whereGpsTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAlarm whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAlarm whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAlarm whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAlarm whereObdDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAlarm whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleAlarm whereVehicleId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['obd_device_id', 'alarm_id', 'alarm_type', 'alarm_description', 'description', 'latitude', 'longitude', 'gps_time'])]
class VehicleAlarm extends Model
{
    protected function casts(): array
    {
        return [
            'gps_time' => 'datetime',
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
