<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleTripScore extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'trip_distance_km' => 'float',
            'trip_duration_seconds' => 'integer',
            'average_speed' => 'float',
            'max_speed' => 'float',
            'overspeed_events' => 'integer',
            'harsh_acceleration_events' => 'integer',
            'harsh_braking_events' => 'integer',
            'harsh_cornering_events' => 'integer',
            'idle_minutes' => 'integer',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(VehicleTrip::class);
    }
}
