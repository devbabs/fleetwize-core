<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $vehicle_id
 * @property string|null $obd_device_id
 * @property float|null $fuel_consumed
 * @property float|null $distance_km
 * @property float|null $max_speed_km_per_hr
 * @property int|null $brake_times
 * @property int|null $emergency_brake_times
 * @property int|null $speed_up_times
 * @property int|null $emergency_speed_up_times
 * @property float|null $average_speed_km_per_hr
 * @property float|null $max_temperature_celsius
 * @property int|null $max_engine_rpm
 * @property string|null $js_type
 * @property int|null $drive_time_seconds
 * @property int|null $idling_time_seconds
 * @property CarbonImmutable|null $trip_date
 * @property CarbonImmutable|null $start_time
 * @property CarbonImmutable|null $end_time
 * @property float|null $start_odometer
 * @property float|null $end_odometer
 * @property numeric|null $start_latitude
 * @property numeric|null $start_longitude
 * @property numeric|null $end_latitude
 * @property numeric|null $end_longitude
 * @property string|null $start_address
 * @property string|null $end_address
 * @property string|null $driver_unique_id
 * @property string|null $driver_name
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Vehicle|null $vehicle
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip sinceLastMaintenance(\App\Models\Vehicle $vehicle)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip whereAverageSpeedKmPerHr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip whereBrakeTimes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip whereDistanceKm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip whereDriveTimeSeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip whereEmergencyBrakeTimes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip whereEmergencySpeedUpTimes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip whereFuelConsumed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip whereIdlingTimeSeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip whereJsType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip whereMaxEngineRpm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip whereMaxSpeedKmPerHr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip whereMaxTemperatureCelsius($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip whereObdDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip whereSpeedUpTimes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip whereTripDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleTrip whereVehicleId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'vehicle_id', 'obd_device_id', 'fuel_consumed', 'distance_km', 'max_speed_km_per_hr', 'brake_times',
    'emergency_brake_times', 'speed_up_times', 'emergency_speed_up_times', 'average_speed_km_per_hr',
    'max_temperature_celsius', 'max_engine_rpm', 'js_type', 'drive_time_seconds', 'idling_time_seconds',
    'trip_date', 'start_time', 'end_time', 'start_odometer', 'end_odometer', 'start_latitude',
    'start_longitude', 'end_latitude', 'end_longitude', 'start_address', 'end_address',
    'driver_unique_id', 'driver_name',
])]
class VehicleTrip extends Model
{
    protected function casts(): array
    {
        return [
            'trip_date' => 'date',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'start_odometer' => 'float',
            'end_odometer' => 'float',
            'start_latitude' => 'float',
            'start_longitude' => 'float',
            'end_latitude' => 'float',
            'end_longitude' => 'float',
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
     * @param  Builder<VehicleTrip>  $query
     * @return Builder<VehicleTrip>
     */
    public function scopeSinceLastMaintenance(Builder $query, Vehicle $vehicle): Builder
    {
        $lastMaintenance = $vehicle->maintenanceRecords()->latest('maintained_at')->first();

        return $query->where('vehicle_id', $vehicle->id)
            ->when($lastMaintenance, fn ($q) => $q->where('start_time', '>=', $lastMaintenance->maintained_at));
    }
}
