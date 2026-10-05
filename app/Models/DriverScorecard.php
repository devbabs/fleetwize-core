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

    protected $casts = [
        'score_breakdown' => 'array',

        'period_start' => 'date',
        'period_end' => 'date',

        'score' => 'float',
        'risk_score' => 'float',
        'speeding_score' => 'float',
        'eco_score' => 'float',
        'fatigue_score' => 'float',
        'distraction_score' => 'float',
    ];

    public function companyUser()
    {
        return $this->belongsTo(
            CompanyUser::class
        );
    }
}
