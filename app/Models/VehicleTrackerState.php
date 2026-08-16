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
#[Fillable(['vehicle_id', 'latitude', 'longitude', 'speed', 'heading', 'ignition_on', 'battery_voltage', 'fuel_level', 'reported_at', 'raw_payload'])]
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
