<?php

namespace App\Http\Resources\Api\AutoX;

use App\Models\VehicleTrip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VehicleTrip
 */
class VehicleTripResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicle_id,
            'distance_km' => $this->distance_km,
            'fuel_consumed' => $this->fuel_consumed,
            'max_speed_km_per_hr' => $this->max_speed_km_per_hr,
            'average_speed_km_per_hr' => $this->average_speed_km_per_hr,
            'max_engine_rpm' => $this->max_engine_rpm,
            'max_temperature_celsius' => $this->max_temperature_celsius,
            'brake_times' => $this->brake_times,
            'emergency_brake_times' => $this->emergency_brake_times,
            'speed_up_times' => $this->speed_up_times,
            'emergency_speed_up_times' => $this->emergency_speed_up_times,
            'drive_time_seconds' => $this->drive_time_seconds,
            'idling_time_seconds' => $this->idling_time_seconds,
            'trip_date' => $this->trip_date?->toDateString(),
            'start_time' => $this->start_time?->toIso8601String(),
            'end_time' => $this->end_time?->toIso8601String(),
        ];
    }
}
