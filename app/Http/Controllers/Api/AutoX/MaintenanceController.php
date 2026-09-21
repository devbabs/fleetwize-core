<?php

namespace App\Http\Controllers\Api\AutoX;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceAlert;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceSchedule;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    //
    public function schedules(Vehicle $vehicle): JsonResponse
    {
        $schedules = MaintenanceSchedule::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('active', true)
            ->get()
            ->map(fn (MaintenanceSchedule $schedule) => [
                'id' => $schedule->id,
                'name' => $schedule->name,
                'distance_interval_km' => $schedule->distance_interval_km,
                'time_interval_days' => $schedule->time_interval_days,
                'active' => $schedule->active,
            ]);

        return response()->json([
            'success' => true,
            'data' => $schedules,
        ]);
    }

    public function alerts(Vehicle $vehicle): JsonResponse
    {
        $alerts = MaintenanceAlert::query()
            ->with('maintenanceSchedule:id,name')
            ->where('vehicle_id', $vehicle->id)
            ->where('acknowledged', false)
            ->latest('alerted_at')
            ->get()
            ->map(fn (MaintenanceAlert $alert) => [
                'id' => $alert->id,
                'maintenance_schedule_id' => $alert->maintenance_schedule_id,
                'schedule_name' => $alert->maintenanceSchedule?->name,
                'title' => $alert->title,
                'description' => $alert->description,
                'alerted_at' => $alert->alerted_at?->toIso8601String(),
                'acknowledged' => $alert->acknowledged,
            ]);

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

    public function storeRecord(Request $request, Vehicle $vehicle): JsonResponse 
    {
        $validated = $request->validate([
            'maintenance_schedule_id' => [
                'required',
                'exists:maintenance_schedules,id',
            ],
            'odometer_km' => [
                'required',
                'integer',
                'min:0',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        $record = MaintenanceRecord::create([
            'vehicle_id' => $vehicle->id,
            'maintenance_schedule_id' => $validated['maintenance_schedule_id'],
            'maintained_at' => now()->toDateString(),
            'odometer_km' => $validated['odometer_km'],
            'notes' => $validated['notes'] ?? null,
        ]);

        MaintenanceAlert::query()
            ->where('vehicle_id', $vehicle->id)
            ->where(
                'maintenance_schedule_id',
                $validated['maintenance_schedule_id']
            )
            ->update([
                'acknowledged' => true,
            ]);

        return response()->json([
            'message' => 'Maintenance recorded successfully.',
            'data' => $record,
        ]);
    }
}
