<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverScorecard extends Model
{
    //
    protected $guarded = [];

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
