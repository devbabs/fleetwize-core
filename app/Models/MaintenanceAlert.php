<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceAlert extends Model
{
    //
    protected $guarded = [];

    protected $casts = [
        'alerted_at' => 'date',
        'acknowledged' => 'boolean',
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
