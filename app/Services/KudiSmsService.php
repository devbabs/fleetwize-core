<?php

namespace App\Services;

use App\Models\Company;
use App\Models\SystemLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * On-demand REST pulls against the self-hosted Traccar instance — used for
 * an admin "force refresh" or backfilling state outside the normal webhook
 * flow (see App\Jobs\ProcessTrackerTelemetry for the primary ingestion path).
 */
class KudiSmsService
{
    /**
     * @param  string|array<int, string|null>  $recipients
     */
    public function send(string|array $recipients, string $message): bool
    {
        $numbers = collect(is_array($recipients) ? $recipients : explode(',', $recipients))
            ->map(fn ($phone) => $this->normalizePhone($phone))
            ->filter()
            ->unique()
            ->values();

        if ($numbers->isEmpty()) {
            return false;
        }

        $response = Http::timeout(15)->get(config('services.kudisms.url'), [
            'token' => config('services.kudisms.token'),
            'senderID' => config('services.kudisms.sender_id'),
            'recipients' => $numbers->implode(','),
            'message' => $message,
            'gateway' => config('services.kudisms.gateway'),
        ]);

        if (! $response->successful()) {
            Log::error('KudiSMS request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'recipients' => $numbers->all(),
            ]);

            return false;
        }

        return true;
    }

    protected function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if (! $digits) {
            return null;
        }

        // Convert local 11-digit Nigerian format (080..., 081..., 070..., 090..., 091...) to 234...
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return '234' . substr($digits, 1);
        }

        // Already in 234XXXXXXXXXX format
        if (strlen($digits) === 13 && str_starts_with($digits, '234')) {
            return $digits;
        }

        return $digits;
    }
}
