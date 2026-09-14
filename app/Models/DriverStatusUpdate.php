<?php

namespace App\Models;

use App\Models\CompanyUser;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverStatusUpdate extends Model
{
    //
    protected $fillable = [
        'company_user_id',
        'vehicle_id',
        'status',
        'notes',
        'latitude',
        'longitude',
    ];

    public const STATUSES = [
        'available',
        'en_route',
        'loading',
        'offloading',
        'delayed',
        'breakdown',
        'off_duty',
    ];

    public function companyUser(): BelongsTo
    {
        return $this->belongsTo(CompanyUser::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
