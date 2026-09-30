<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverScorecard extends Model
{
    //
    protected $fillable = [
        'company_user_id',
        'period_type',
        'period_start',
        'period_end',

        'trip_count',

        'score',
        'safety_score',
        'efficiency_score',

        'distance_km',
        'duration_seconds',

        'overspeed_events',
        'severe_overspeed_events',

        'harsh_acceleration_events',
        'harsh_braking_events',
        'harsh_cornering_events',

        'idle_minutes',

        'grade',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function companyUser()
    {
        return $this->belongsTo(
            CompanyUser::class
        );
    }
}
