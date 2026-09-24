<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleEvent extends Model
{
    //
    protected $guarded = [];

    protected $casts = [
        'attributes' => 'array',
        'event_time' => 'datetime',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
