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
 * @property CarbonImmutable|null $acknowledged_at
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
#[Fillable(['vehicle_id', 'obd_device_id', 'alarm_id', 'alarm_type', 'alarm_description', 'description', 'latitude', 'longitude', 'gps_time', 'acknowledged_at'])]
class VehicleAlarm extends Model
{
    /**
     * High/medium/low severity tiers, matching the badge tiers the Alarms
     * & Alerts page already renders. Genuine `alarm`-type events (Traccar's
     * own alarm taxonomy, e.g. sos/tamper/powerCut in attributes.alarm) are
     * the only ones that warrant "high" — everything else here is a routine
     * status transition, not something urgent.
     */
    private const HIGH_SEVERITY_ALARMS = ['sos', 'tamper', 'powerCut'];

    protected function casts(): array
    {
        return [
            'gps_time' => 'datetime',
            'acknowledged_at' => 'datetime',
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
     * Derived, not stored — a pure function of alarm_type/alarm_description,
     * so retuning the tier mapping never needs a backfill.
     */
    public function severity(): int
    {
        if ($this->alarm_type === 'alarm') {
            return in_array($this->alarm_description, self::HIGH_SEVERITY_ALARMS, true) ? 4 : 2;
        }

        if ($this->alarm_type === 'deviceOffline') {
            return 2;
        }

        // Crossing a defined boundary is routine, not urgent, by default —
        // same tier as any other unrecognized status transition.
        if ($this->alarm_type === 'geofenceEnter' || $this->alarm_type === 'geofenceExit') {
            return 1;
        }

        return 1;
    }
}
