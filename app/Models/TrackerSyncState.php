<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackerSyncState extends Model
{
    //
    protected $guarded = [];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];
}
