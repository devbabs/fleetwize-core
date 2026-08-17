<?php

namespace App\Events;

use App\Models\Vehicle;
use App\Models\VehicleAlarm;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast the moment a tracker alarm/event lands, on the same
 * per-company channel VehicleTrackerStateUpdated already uses (one
 * channel per company, not one per concern — avoids channel proliferation
 * for pages that only care about one or the other). Fires from inside
 * ProcessTrackerEvent, already on a Horizon worker — same ShouldBroadcastNow
 * reasoning as VehicleTrackerStateUpdated.
 */
class VehicleAlarmRecorded implements ShouldBroadcastNow
{
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        protected Vehicle $vehicle,
        protected VehicleAlarm $alarm,
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
        return 'vehicle.alarm';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'vehicleId' => $this->vehicle->id,
            'alarmId' => $this->alarm->id,
            'alarmType' => $this->alarm->alarm_type,
            'alarmDescription' => $this->alarm->alarm_description,
            'severity' => $this->alarm->severity(),
            'gpsTime' => $this->alarm->gps_time?->toIso8601String(),
            'latitude' => $this->alarm->latitude,
            'longitude' => $this->alarm->longitude,
        ];
    }
}
