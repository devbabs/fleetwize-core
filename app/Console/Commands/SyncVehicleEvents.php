<?php

namespace App\Console\Commands;

use App\Models\TrackerSyncState;
use App\Models\Vehicle;
use App\Models\VehicleEvent;
use App\Models\VehicleRoutePoint;
use App\Models\VehicleTrip;
use App\Services\Tracking\TraccarService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Traccar computes trip aggregates server-side (/api/reports/trips) — a
 * trip is only known once it's over, so unlike positions/events this has
 * to be pulled on an interval rather than pushed. Scheduled via
 * bootstrap/app.php's withSchedule().
 */
class SyncVehicleEvents extends Command
{
    protected $signature = 'tracker:sync-events';

    protected $description = 'Pull event data from Traccar and sync it into vehicle_events.';

    public function handle(TraccarService $traccar): int
    {
        $syncState = TrackerSyncState::firstOrCreate(
            ['type' => 'events'],
            [
                'last_synced_at' => now()->startOfMonth(),
            ]
        );

        $from = Carbon::parse($syncState->last_synced_at);
        $to = Carbon::now();

        $hadErrors = false;
        $totalEvents = 0;

        $this->info("Syncing events from {$from} to {$to}");

        Vehicle::query()
            ->whereNotNull('traccar_device_id')
            ->chunkById(100, function ($vehicles) use (
                $traccar,
                $from,
                $to,
                &$hadErrors,
                &$totalEvents
            ) {

                foreach ($vehicles as $vehicle) {

                    try {

                        $events = $traccar->eventReport(
                            $vehicle->traccar_device_id,
                            $from,
                            $to
                        );

                        $allowedEvents = [
                            'ignitionOn',
                            'ignitionOff',
                            'deviceMoving',
                            'deviceStopped',
                            'alarm',
                            'geofenceEnter',
                            'geofenceExit',
                            'maintenance',
                        ];

                        foreach ($events as $event) {

                                $eventType = $event['type'] ?? null;

                                if (! in_array($eventType, $allowedEvents, true)) {
                                    continue;
                                }

                            VehicleEvent::updateOrCreate(
                                [
                                    'traccar_event_id' => $event['id'],
                                ],
                                [
                                    'traccar_device_id' => $vehicle->traccar_device_id,
                                    'vehicle_id' => $vehicle->id,
                                    'traccar_position_id' => $event['positionId'] ?? null,
                                    'event_type' => $event['type'] ?? null,
                                    'alarm' => $event['attributes']['alarm'] ?? null,
                                    'event_time' => $event['eventTime'] ?? null,
                                    'attributes' => $event['attributes'] ?? [],
                                ]
                            );

                            $totalEvents++;
                        }

                        $this->line(
                            "{$vehicle->name}: synced "
                            . count($events)
                            . " events"
                        );

                    } catch (Throwable $e) {

                        $hadErrors = true;

                        report($e);

                        $this->error(
                            "{$vehicle->name}: {$e->getMessage()}"
                        );
                    }
                }
            });

        if (! $hadErrors) {

            $syncState->update([
                'last_synced_at' => $to,
            ]);

        } else {

            $this->warn(
                'Some vehicles failed. Sync cursor not advanced.'
            );
        }

        $this->info(
            "Event sync completed. {$totalEvents} events processed."
        );

        return self::SUCCESS;
    }
}
