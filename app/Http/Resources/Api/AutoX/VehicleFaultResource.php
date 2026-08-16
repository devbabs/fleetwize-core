<?php

namespace App\Http\Resources\Api\AutoX;

use App\Models\VehicleFault;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VehicleFault
 */
class VehicleFaultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicle_id,
            'obd_device_id' => $this->obd_device_id,
            'obd_code' => $this->obd_code,
            'obd_description' => $this->obd_description,
            'meaning' => $this->meaning,
            'log_time' => $this->log_time?->toIso8601String(),
            'note' => $this->note,
            'severity' => $this->severity,
            'common_causes' => $this->common_causes,
            'symptoms' => $this->symptoms,
            'possible_fixes' => $this->possible_fixes,
        ];
    }
}
