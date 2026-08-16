<?php

namespace App\Http\Controllers\Api\AutoX;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AutoX\VehicleLiveStateResource;
use App\Http\Resources\Api\AutoX\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VehicleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $vehicles = $request->user()->vehicles()
            ->withCount(['trips', 'faults'])
            ->withSum('trips', 'distance_km')
            ->with('trackerState')
            ->get();

        return VehicleResource::collection($vehicles);
    }

    public function show(Request $request, Vehicle $vehicle): VehicleResource
    {
        $this->authorizeOwnership($request, $vehicle);

        $vehicle
            ->loadCount(['trips', 'faults'])
            ->loadSum('trips', 'distance_km')
            ->load(['trips' => fn ($query) => $query->latest('start_time')->limit(20)])
            ->load(['faults' => fn ($query) => $query->whereNull('cleared_at')->latest('log_time')])
            ->load('trackerState');

        return new VehicleResource($vehicle);
    }

    public function liveState(Request $request, Vehicle $vehicle): VehicleLiveStateResource
    {
        $this->authorizeOwnership($request, $vehicle);

        $state = $vehicle->trackerState;

        abort_if($state === null, 404, 'No live tracker data yet for this vehicle.');

        return new VehicleLiveStateResource($state);
    }

    protected function authorizeOwnership(Request $request, Vehicle $vehicle): void
    {
        abort_unless($vehicle->user_id === $request->user()->id, 403);
    }
}
