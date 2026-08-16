<?php

namespace App\Http\Controllers\Company\Concerns;

use App\Services\Tracking\TraccarService;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Attaching a tracker IMEI to a vehicle is checked against the real Traccar
 * instance rather than just format-validated, so a mistyped or never-seen
 * device can't silently sit on a vehicle record.
 */
trait ValidatesTrackerImei
{
    protected function assertTrackerImeiIsValid(?string $imei): void
    {
        if (! $imei) {
            return;
        }

        try {
            $device = app(TraccarService::class)->findDeviceByImei($imei);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'obd_device_imei' => 'Could not reach the tracker verification service right now. Try again shortly.',
            ]);
        }

        if (! $device) {
            throw ValidationException::withMessages([
                'obd_device_imei' => 'No tracker with this IMEI has been seen by Traccar yet. Confirm the device has reported at least once.',
            ]);
        }
    }
}
