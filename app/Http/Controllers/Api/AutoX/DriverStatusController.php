<?php

namespace App\Http\Controllers\Api\AutoX;

use App\Http\Controllers\Controller;
use App\Models\DriverStatusUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DriverStatusController extends Controller
{
    //
    public function index(Request $request): JsonResponse
    {
        $companyUser = $request->user()
            ->companyUser()
            ->where('role', 'driver')
            ->firstOrFail();

        $updates = DriverStatusUpdate::query()
            ->where('company_user_id', $companyUser->id)
            ->latest()
            ->paginate(20);

        return response()->json($updates);
    }

    public function store(Request $request): JsonResponse
    {
        $companyUser = $request->user()
            ->companyUser()
            ->where('role', 'driver')
            ->firstOrFail();

        $assignment = $companyUser->activeAssignment();

        abort_if(
            ! $assignment,
            422,
            'You do not currently have an assigned vehicle.'
        );

        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(DriverStatusUpdate::STATUSES),
            ],

            'notes' => ['nullable', 'string'],

            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $statusUpdate = DriverStatusUpdate::create([
            'company_user_id' => $companyUser->id,
            'vehicle_id' => $assignment->vehicle_id,

            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,

            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ]);

        return response()->json([
            'message' => 'Status updated successfully.',
            'data' => [
                'id' => $statusUpdate->id,
                'status' => $statusUpdate->status,
                'notes' => $statusUpdate->notes,
                'vehicle_id' => $statusUpdate->vehicle_id,
                'created_at' => $statusUpdate->created_at,
            ],
        ], 201);
    }
}
