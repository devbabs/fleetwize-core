<?php

namespace App\Jobs;

use App\Events\VehicleAlarmRecorded;
use App\Models\Geofence;
use App\Models\Vehicle;
use App\Models\VehicleAlarm;
use App\Services\Tracking\TraccarPayloadNormalizer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Ingests Traccar's event.forward webhook — a separate mechanism from
 * position forward (see ProcessTrackerTelemetry), firing whenever Traccar
 * records an event (ignitionOn/Off, deviceMoving/Stopped, alarm, etc.).
 * Unlike tracker state, alarms are an append-only log, not a "latest
 * state" to overwrite — each event creates its own VehicleAlarm row.
 */
class ProcessTrackerEvent implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $event
     * @param  array<string, mixed>  $device
     */
    public function __construct(
        public array $event,
        public array $device,
    ) {}

    public function handle(): void
    {
        $imei = TraccarPayloadNormalizer::imei($this->device);

        if (! $imei) {
            Log::warning('Tracker event received with no device uniqueId (IMEI).', ['device' => $this->device]);

            return;
        }

        $vehicle = Vehicle::query()->where('obd_device_imei', $imei)->first();

        if (! $vehicle) {
            Log::warning("Tracker event received for an IMEI with no matching vehicle: {$imei}");

            return;
        }

        $alarm = VehicleAlarm::query()->create([
            'vehicle_id' => $vehicle->id,
            ...$this->withGeofenceDescription(TraccarPayloadNormalizer::normalizeEvent($this->event, $this->device)),
        ]);

        try {
            broadcast(new VehicleAlarmRecorded($vehicle, $alarm));
        } catch (Throwable $e) {
            // The row above already saved — a broadcast hiccup shouldn't
            // fail/retry the whole ingestion job, it just means this one
            // alarm doesn't push live (it'll still show up on next page load).
            Log::warning('Failed to broadcast vehicle alarm.', [
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * TraccarPayloadNormalizer::normalizeEvent() stays pure/stateless, so
     * the geofence-name lookup (which needs the DB) happens here instead —
     * turns the raw "geofenceEnter"/"geofenceExit" type into something
     * readable, falling back to the raw type string if the geofence can't
     * be resolved (e.g. it was deleted locally after Traccar already fired
     * the event, or the event predates this feature).
     *
     * @param  array<string, mixed>  $alarmData
     * @return array<string, mixed>
     */
    protected function withGeofenceDescription(array $alarmData): array
    {
        if (! in_array($alarmData['alarm_type'] ?? null, ['geofenceEnter', 'geofenceExit'], true)) {
            return $alarmData;
        }

        $geofenceId = $this->event['attributes']['geofenceId'] ?? null;
        $geofence = $geofenceId ? Geofence::query()->where('traccar_geofence_id', $geofenceId)->first() : null;

        if ($geofence) {
            $action = $alarmData['alarm_type'] === 'geofenceEnter' ? 'Entered' : 'Exited';
            $alarmData['alarm_description'] = "{$action} geofence: {$geofence->name}";
        }

        return $alarmData;
    }
}
