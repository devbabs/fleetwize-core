<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Company\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Models\VehicleIssue;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IssueController extends Controller
{
    use ResolvesCompany;

    public function index(Request $request): Response
    {
        $company = $this->currentCompany($request);

        $issues = VehicleIssue::query()
            ->where('company_id', $company->id)
            ->with(['vehicle:id,license_plate,make,model', 'reporter:id,first_name,last_name'])
            ->latest('reported_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (VehicleIssue $issue) => [
                'id' => $issue->id,
                'vehicle' => $issue->vehicle->license_plate ?? '—',
                'summary' => $issue->summary,
                'description' => $issue->description,
                'priority' => $issue->priority,
                'status' => $issue->status,
                'reportedAt' => $issue->reported_at?->toIso8601String(),
                'reportedBy' => $issue->reporter?->full_name,
            ]);

        return Inertia::render('company/issues/index', [
            'issues' => $issues,
        ]);
    }
}
