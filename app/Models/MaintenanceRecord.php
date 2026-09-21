<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRecord extends Model
{
    //
    protected $guarded = [];

    protected $casts = [
        'maintained_at' => 'date',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function maintenanceSchedule(): BelongsTo
    {
        return $this->belongsTo(MaintenanceSchedule::class);
    }
}
