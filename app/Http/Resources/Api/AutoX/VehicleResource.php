<?php

namespace App\Http\Resources\Api\AutoX;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Vehicle
 */
class VehicleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Not whenLoaded(): that helper short-circuits to null for a loaded-but-empty
        // relation before ever calling its value callback, which isn't what we want
        // for a derived field that should default to false/null, not be dropped.
        $trackerState = $this->relationLoaded('trackerState') ? $this->trackerState : null;

        return [
            'id' => $this->id,
            'make' => $this->make,
            'model' => $this->model,
            'year' => $this->year,
            'vehicle' => $this->vehicle,
            'color' => $this->color,
            'mileage' => $this->mileage + ($this->trips_sum_distance_km ?? 0),
            'tracker_id' => $this->obd_device_id,
            'tracker_imei' => $this->obd_device_imei,
            'tracker_phone_number' => $this->tracker_phone_number,
            'vin' => $this->vin,
            'trips_count' => $this->whenCounted('trips'),
            'alarms_count' => $this->whenCounted('faults'),
            'is_online' => $trackerState?->isOnline() ?? false,
            'last_seen_at' => $trackerState?->reported_at?->toIso8601String(),
            'trips' => VehicleTripResource::collection($this->whenLoaded('trips')),
            'alarms' => VehicleFaultResource::collection($this->whenLoaded('faults')),
        ];
    }
}
