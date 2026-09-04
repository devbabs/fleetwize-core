<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Latest-known live telemetry per vehicle, upserted from the Traccar middleware webhook.
 *
 * @property int $id
 * @property int $vehicle_id
 * @property numeric|null $latitude
 * @property numeric|null $longitude
 * @property float|null $speed
 * @property float|null $heading
 * @property bool|null $ignition_on
 * @property float|null $battery_voltage
 * @property float|null $fuel_level
 * @property int|null $engine_rpm
 * @property float|null $engine_load
 * @property float|null $obd_speed
 * @property bool|null $is_moving
 * @property float|null $battery_level
 * @property int|null $satellite_count
 * @property int|null $signal_strength
 * @property float|null $engine_hours
 * @property bool|null $is_blocked
 * @property bool|null $is_charging
 * @property CarbonImmutable|null $reported_at
 * @property array<array-key, mixed>|null $raw_payload
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Vehicle|null $vehicle
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrackerState newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrackerState newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrackerState query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrackerState whereBatteryVoltage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrackerState whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrackerState whereFuelLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrackerState whereHeading($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrackerState whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrackerState whereIgnitionOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrackerState whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrackerState whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrackerState whereRawPayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrackerState whereReportedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrackerState whereSpeed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrackerState whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrackerState whereVehicleId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'vehicle_id', 'latitude', 'longitude', 'speed', 'heading', 'ignition_on', 'battery_voltage',
    'fuel_level', 'reported_at', 'raw_payload', 'engine_rpm', 'engine_load', 'obd_speed', 'is_moving',
    'battery_level', 'satellite_count', 'signal_strength', 'engine_hours', 'is_blocked', 'is_charging',
    'unique_id', 'device_status', 'protocol', 'altitude', 'gps_valid', 'device_time', 'server_time', 'odometer',
    'obd_odometer', 'total_distance', 'hard_cornering_count', 'hard_acceleration_count', 'hard_deceleration_count',
])]
class VehicleTrackerState extends Model
{
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'speed' => 'float',
            'heading' => 'float',
            'ignition_on' => 'boolean',
            'battery_voltage' => 'float',
            'fuel_level' => 'float',
            'reported_at' => 'datetime',
            'raw_payload' => 'array',
            'engine_rpm' => 'integer',
            'engine_load' => 'float',
            'obd_speed' => 'float',
            'is_moving' => 'boolean',
            'battery_level' => 'float',
            'satellite_count' => 'integer',
            'signal_strength' => 'integer',
            'engine_hours' => 'float',
            'is_blocked' => 'boolean',
            'is_charging' => 'boolean',

            'unique_id' => 'string',
            'device_status' => 'string',
            'protocol' => 'string',
            'altitude' => 'float',
            'gps_valid' => 'boolean',
            'device_time' => 'datetime',
            'server_time' => 'datetime',
            'odometer' => 'integer',
            'obd_odometer' => 'integer',
            'total_distance' => 'float',
            'hard_cornering_count' => 'integer',
            'hard_acceleration_count' => 'integer',
            'hard_deceleration_count' => 'integer',

        ];
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function isOnline(): bool
    {
        return $this->reported_at?->gt(now()->subMinutes(5)) ?? false;
    }
}
