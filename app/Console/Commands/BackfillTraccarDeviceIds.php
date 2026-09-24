<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use App\Models\VehicleTrip;
use App\Services\Tracking\TraccarService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Traccar computes trip aggregates server-side (/api/reports/trips) — a
 * trip is only known once it's over, so unlike positions/events this has
 * to be pulled on an interval rather than pushed. Scheduled via
 * bootstrap/app.php's withSchedule().
 */
class BackfillTraccarDeviceIds extends Command
{
    protected $signature = 'tracker:backfill-device-ids';

    protected $description =
        'Populate traccar_device_id from IMEI';

    public function handle(
        TraccarService $traccar
    ): int {

        Vehicle::query()
            ->whereNotNull('obd_device_imei')
            ->chunkById(100, function ($vehicles) use ($traccar) {

                foreach ($vehicles as $vehicle) {

                    $device = $traccar->findDeviceByImei(
                        $vehicle->obd_device_imei
                    );

                    if (! $device) {

                        $this->warn(
                            "{$vehicle->name}: device not found"
                        );

                        continue;
                    }

                    $vehicle->update([
                        'traccar_device_id' => $device['id'],
                    ]);

                    $this->info(
                        "{$vehicle->name}: {$device['id']}"
                    );
                }
            });

        return self::SUCCESS;
    }
}
