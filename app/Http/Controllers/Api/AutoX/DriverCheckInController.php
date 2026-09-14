<?php

namespace App\Http\Controllers\Api\AutoX;

use App\Http\Controllers\Controller;
use App\Models\DriverCheckIn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DriverCheckInController extends Controller
{
    //
    public function today(Request $request): JsonResponse
    {
        $companyUser = $request->user()
            ->companyUser()
            ->where('role', 'driver')
            ->firstOrFail();

        $assignment = $companyUser->activeAssignment();

        if (! $assignment) {
            return response()->json([
                'assigned' => false,
                'message' => 'No active vehicle assignment found.',
            ]);
        }

        $checkIn = DriverCheckIn::query()
            ->where('company_user_id', $companyUser->id)
            ->where('vehicle_id', $assignment->vehicle_id)
            ->whereDate('created_at', today())
            ->latest()
            ->first();

        return response()->json([
            'assigned' => true,
            'checked_in' => $checkIn !== null,
            'vehicle' => [
                'id' => $assignment->vehicle->id,
                'name' => $assignment->vehicle->name,
            ],
            'data' => $checkIn,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $companyUser = $request->user()
            ->companyUser()
            ->where('role', 'driver')
            ->firstOrFail();

        $assignment = $companyUser->assignments()
            ->whereDate('start_date', '<=', today())
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', today());
            })
            ->latest()
            ->first();

        abort_if(
            ! $assignment,
            422,
            'You do not currently have an assigned vehicle.'
        );

        $alreadyCheckedIn = DriverCheckIn::query()
            ->where('company_user_id', $companyUser->id)
            ->where('vehicle_id', $assignment->vehicle_id)
            ->whereDate('created_at', today())
            ->exists();

        if ($alreadyCheckedIn) {
            return response()->json([
                'message' => 'You have already completed today\'s vehicle check-in.',
            ], 422);
        }

        $validated = $request->validate([
            'tyre' => ['required', Rule::in(['good', 'bad'])],
            'vehicle_condition' => ['required', Rule::in(['good', 'bad'])],
            'engine_oil' => ['required', Rule::in(['good', 'bad'])],
            'water_level' => ['required', Rule::in(['good', 'bad'])],

            'odometer' => ['nullable', 'integer', 'min:0'],
            'fuel_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],

            'notes' => ['nullable', 'string'],

            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $status = collect([
            $validated['tyre'],
            $validated['vehicle_condition'],
            $validated['engine_oil'],
            $validated['water_level'],
        ])->contains('bad')
            ? 'fail'
            : 'pass';

        $checkIn = DriverCheckIn::create([
            'company_user_id' => $companyUser->id,
            'vehicle_id' => $assignment->vehicle_id,

            'tyre' => $validated['tyre'],
            'vehicle_condition' => $validated['vehicle_condition'],
            'engine_oil' => $validated['engine_oil'],
            'water_level' => $validated['water_level'],

            'status' => $status,

            'odometer' => $validated['odometer'] ?? null,
            'fuel_percentage' => $validated['fuel_percentage'] ?? null,

            'notes' => $validated['notes'] ?? null,

            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ]);

        return response()->json([
            'message' => 'Vehicle check-in completed successfully.',
            'data' => $checkIn,
        ], 201);
    }
}
