<?php

namespace App\Events;

use App\Models\Vehicle;
use App\Models\VehicleTrackerState;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast the moment a tracker's position lands, so the Live Tracking
 * map, Vehicles list, and Vehicle detail page can all patch it in without
 * a refresh. Fires from inside ProcessTrackerTelemetry, which already runs
 * on a Horizon worker — ShouldBroadcastNow (not ShouldBroadcast) sends
 * inline instead of adding a second queue hop per tracker ping.
 */
class VehicleTrackerStateUpdated implements ShouldBroadcastNow
{
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        protected Vehicle $vehicle,
        protected VehicleTrackerState $trackerState,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("company.{$this->vehicle->company_id}.vehicles")];
    }

    public function broadcastAs(): string
    {
        return 'vehicle.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->vehicle->id,
            'name' => $this->vehicle->name,
            'licensePlate' => $this->vehicle->license_plate,
            'category' => $this->vehicle->category,
            'isOnline' => $this->trackerState->isOnline(),
            'latitude' => $this->trackerState->latitude,
            'longitude' => $this->trackerState->longitude,
            'speed' => $this->trackerState->speed,
            'heading' => $this->trackerState->heading,
            'ignitionOn' => $this->trackerState->ignition_on,
            'fuelLevel' => $this->trackerState->fuel_level,
            'batteryVoltage' => $this->trackerState->battery_voltage,
            'reportedAt' => $this->trackerState->reported_at?->toIso8601String(),
        ];
    }
}
