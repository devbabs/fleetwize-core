<?php

namespace App\Models;

use App\Models\MaintenanceAlert;
use App\Models\MaintenanceRecord;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceSchedule extends Model
{
    //
    protected $guarded = [];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(MaintenanceAlert::class);
    }
}
