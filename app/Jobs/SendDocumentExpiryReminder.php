<?php

namespace App\Jobs;

use App\Events\VehicleAlarmRecorded;
use App\Mail\DocumentExpiryReminderMail;
use App\Models\Geofence;
use App\Models\Vehicle;
use App\Models\VehicleAlarm;
use App\Models\VehicleDocument;
use App\Services\KudiSmsService;
use App\Services\Tracking\TraccarPayloadNormalizer;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Ingests Traccar's event.forward webhook — a separate mechanism from
 * position forward (see ProcessTrackerTelemetry), firing whenever Traccar
 * records an event (ignitionOn/Off, deviceMoving/Stopped, alarm, etc.).
 * Unlike tracker state, alarms are an append-only log, not a "latest
 * state" to overwrite — each event creates its own VehicleAlarm row.
 */
class SendDocumentExpiryReminder implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $vehicleDocumentId,
        public string $milestone,
        public int $daysRemaining,
    ) {}

    public function handle(KudiSmsService $sms): void
    {
        $doc = VehicleDocument::with(['vehicle.company.users', 'vehicle.driver', 'document'])
            ->find($this->vehicleDocumentId);

        if (! $doc || ! $doc->vehicle) {
            return;
        }

        $vehicle = $doc->vehicle;
        $docTitle = $doc->document?->title ?? 'Vehicle Document';
        $plate = $vehicle->plate_number ?? $vehicle->name ?? "Vehicle #{$vehicle->id}";
        $expiryFormatted = Carbon::parse($doc->expires_at)->format('d M Y');

        // 1. Resolve Admin / Fleet Manager recipients for this company
        $managers = $vehicle->company?->users()
            ->whereIn('role', ['admin', 'company_admin', 'fleet_manager'])
            ->get() ?? collect();

        // 2. Include assigned driver for 7d, 3d, and expired milestones
        $includeDriver = in_array($this->milestone, ['7_days', '3_days', 'expired'], true);
        $driver = $includeDriver ? $vehicle->driver : null;

        $recipients = $managers->when($driver, fn ($c) => $c->push($driver))->unique('id');

        // 3. Send Email to all recipients with an email address
        foreach ($recipients as $user) {
            if (! empty($user->email)) {
                Mail::to($user->email)->send(
                    new DocumentExpiryReminderMail($doc, $this->daysRemaining, $this->milestone)
                );
            }
        }

        // 4. Send SMS on 14_days, 7_days, 3_days, and expired (comma-separated in one call)
        if ($this->milestone !== '30_days') {
            $phones = $recipients->pluck('phone')->filter()->all();

            $statusText = $this->daysRemaining <= 0
                ? "EXPIRED on {$expiryFormatted}"
                : "expires in {$this->daysRemaining} day(s) ({$expiryFormatted})";

            // Keep SMS <= 160 chars so it bills as 1 SMS page on KudiSMS
            $smsBody = "Fleetwize Alert: {$docTitle} for {$plate} {$statusText}. Please renew promptly.";

            $sms->send($phones, $smsBody);
        }
    }
}
