<?php

namespace App\Console\Commands;

use App\Models\VehicleTrip;
use App\Services\Tracking\TraccarService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Traccar computes trip aggregates server-side (/api/reports/trips) — a
 * trip is only known once it's over, so unlike positions/events this has
 * to be pulled on an interval rather than pushed. Scheduled via
 * bootstrap/app.php's withSchedule().
 */
class BackfillTripAddresses extends Command
{
    protected $signature = 'tracker:backfill-trip-addresses
                            {--days=30 : Look back N days}
                            {--vehicle= : Limit to a single vehicle ID}';

    protected $description = 'Reverse-geocode missing start_address and end_address on vehicle_trips with rate limiting and caching.';

    public function handle(TraccarService $traccar): int
    {
        $query = VehicleTrip::query()
            ->where('start_time', '>=', now()->subDays((int) $this->option('days'))->startOfDay())
            ->where(function ($q) {
                $q->whereNull('start_address')
                    ->orWhere('start_address', '')
                    ->orWhereNull('end_address')
                    ->orWhere('end_address', '');
            })
            ->orderByDesc('start_time');

        if ($vehicleId = $this->option('vehicle')) {
            $query->where('vehicle_id', $vehicleId);
        }

        $trips = $query->get();

        if ($trips->isEmpty()) {
            $this->info('No trips with missing addresses found.');
            return self::SUCCESS;
        }

        $this->info("Found {$trips->count()} trip(s) with missing addresses.");
        $updated = 0;

        foreach ($trips as $trip) {
            $changes = [];

            if (empty($trip->start_address) && $trip->start_latitude !== null && $trip->start_longitude !== null) {
                $addr = $this->lookupAddress($traccar, (float) $trip->start_latitude, (float) $trip->start_longitude);
                if ($addr !== null) {
                    $changes['start_address'] = $addr;
                }
            }

            if (empty($trip->end_address) && $trip->end_latitude !== null && $trip->end_longitude !== null) {
                $addr = $this->lookupAddress($traccar, (float) $trip->end_latitude, (float) $trip->end_longitude);
                if ($addr !== null) {
                    $changes['end_address'] = $addr;
                }
            }

            if (! empty($changes)) {
                $trip->update($changes);
                $updated++;
                $this->line("Trip #{$trip->id}: updated " . implode(', ', array_keys($changes)));
            }
        }

        $this->info("Done. Updated {$updated} of {$trips->count()} trip(s).");

        return self::SUCCESS;
    }

    protected function lookupAddress(TraccarService $traccar, float $lat, float $lon): ?string
    {
        // Round to 4 decimal places (~11m) to reuse cached addresses for common stops
        $cacheKey = sprintf('geocode:%.4f:%.4f', $lat, $lon);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $address = $traccar->reverseGeocode($lat, $lon);
        } catch (Throwable $e) {
            $this->warn("Geocode failed ({$lat}, {$lon}): {$e->getMessage()}");
            $address = null;
        }

        // Only cache successful lookups so temporary 204s can be retried later
        if (! empty($address)) {
            Cache::put($cacheKey, $address, now()->addDays(30));
        }

        // Respect Nominatim's 1 req/sec limit on uncached calls
        usleep(1_100_000);

        return $address;
    }
}