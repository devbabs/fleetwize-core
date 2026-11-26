<?php

namespace App\Models;

use App\Models\VehicleDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentReminderLog extends Model
{
    public const MILESTONE_30_DAYS = '30_days';
    public const MILESTONE_14_DAYS = '14_days';
    public const MILESTONE_7_DAYS = '7_days';
    public const MILESTONE_3_DAYS = '3_days';
    public const MILESTONE_EXPIRED = 'expired';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vehicle_document_id',
        'expires_at',
        'milestone',
        'sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function vehicleDocument(): BelongsTo
    {
        return $this->belongsTo(VehicleDocument::class);
    }
}
