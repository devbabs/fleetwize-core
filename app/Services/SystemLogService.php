<?php

namespace App\Services;

use App\Models\Company;
use App\Models\SystemLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * On-demand REST pulls against the self-hosted Traccar instance — used for
 * an admin "force refresh" or backfilling state outside the normal webhook
 * flow (see App\Jobs\ProcessTrackerTelemetry for the primary ingestion path).
 */
class SystemLogService
{
    public function log(
        string $event,
        ?string $description = null,
        ?Model $subject = null,
        array $metadata = [],
        ?Company $company = null,
        ?Request $request = null,
    ): SystemLog
    {
        $request ??= request();

        return SystemLog::create([
            'user_id' => Auth::id(),
            'event' => $event,
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata ?: null,
            'company_id' => $company?->id,
        ]);
    }
}
