<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleRoutePoint extends Model
{
    //
    protected $guarded = [];

    protected $casts = [
        'raw_payload' => 'array',
        'fix_time' => 'datetime',
        'device_time' => 'datetime',
        'server_time' => 'datetime',
        'ignition' => 'boolean',
        'motion' => 'boolean',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function trip()
    {
        return $this->belongsTo(VehicleTrip::class, 'vehicle_trip_id');
    }
}
