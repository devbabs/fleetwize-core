<?php

namespace App\Http\Resources\Api\AutoX;

use App\Models\VehicleTrackerState;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VehicleTrackerState
 */
class VehicleLiveStateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'speed' => $this->speed,
            'heading' => $this->heading,
            'ignition_on' => $this->ignition_on,
            'battery_voltage' => $this->battery_voltage,
            'fuel_level' => $this->fuel_level,
            'reported_at' => $this->reported_at?->toIso8601String(),
            'is_online' => $this->isOnline(),
        ];
    }
}
