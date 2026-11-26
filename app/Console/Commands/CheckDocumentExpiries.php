<?php

namespace App\Console\Commands;

use App\Jobs\SendDocumentExpiryReminder;
use App\Models\DocumentReminderLog;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Models\VehicleDocument;
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
class CheckDocumentExpiries extends Command
{
    protected $signature = 'documents:check-expiry';

    protected $description = 'Send Email and SMS reminders for expiring and expired vehicle documents';

    public function handle(): int
    {
        $today = Carbon::today('Africa/Lagos');

        VehicleDocument::query()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', $today->copy()->addDays(30))
            ->with(['vehicle.company.users', 'vehicle.driver', 'document'])
            ->chunkById(100, function ($documents) use ($today) {
                foreach ($documents as $doc) {
                    $expiryDate = Carbon::parse($doc->expires_at, 'Africa/Lagos')->startOfDay();
                    $daysRemaining = (int) $today->diffInDays($expiryDate, false);

                    $milestone = $this->resolveMilestone($daysRemaining);

                    if (! $milestone) {
                        continue;
                    }

                    $alreadySent = DocumentReminderLog::query()
                        ->where('vehicle_document_id', $doc->id)
                        ->whereDate('expires_at', $expiryDate->toDateString())
                        ->where('milestone', $milestone)
                        ->exists();

                    if ($alreadySent) {
                        continue;
                    }

                    // Keep status column synced in DB
                    $newStatus = $daysRemaining <= 0 ? 'expired' : 'expiring_soon';
                    if ($doc->status !== $newStatus) {
                        $doc->update(['status' => $newStatus]);
                    }

                    SendDocumentExpiryReminder::dispatch($doc->id, $milestone, $daysRemaining);

                    DocumentReminderLog::create([
                        'vehicle_document_id' => $doc->id,
                        'expires_at' => $expiryDate->toDateString(),
                        'milestone' => $milestone,
                        'sent_at' => now(),
                    ]);
                }
            });

        $this->info('Document expiry checks completed.');

        return self::SUCCESS;
    }

    protected function resolveMilestone(int $daysRemaining): ?string
    {
        return match (true) {
            $daysRemaining <= 0 => 'expired',
            $daysRemaining <= 3 => '3_days',
            $daysRemaining <= 7 => '7_days',
            $daysRemaining <= 14 => '14_days',
            $daysRemaining <= 30 => '30_days',
            default => null,
        };
    }
}
