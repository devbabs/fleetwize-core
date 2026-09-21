<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SystemLogController extends Controller
{
    //
    use ResolvesCompany;

    public function index(Request $request): Response
    {
        $company = $this->currentCompany($request);

        $logs = SystemLog::query()
            ->where('company_id', $company->id)
            ->with('user:id,name,email')
            ->latest()
            ->paginate(20)
            ->through(fn ($log) => [
                'id' => $log->id,
                'user' => $log->user?->name,
                'event' => $log->event,
                'description' => $log->description,
                'ipAddress' => $log->ip_address,
                'createdAt' => $log->created_at?->toIso8601String(),
            ]);

        return Inertia::render('company/system-logs/index', [
            'logs' => $logs,
        ]);
    }
}
