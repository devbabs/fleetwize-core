<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $vehicle_service_entry_id
 * @property int $vehicle_issue_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read VehicleIssue $issue
 * @property-read VehicleServiceEntry $serviceEntry
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceEntryIssue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceEntryIssue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceEntryIssue query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceEntryIssue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceEntryIssue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceEntryIssue whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceEntryIssue whereVehicleIssueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceEntryIssue whereVehicleServiceEntryId($value)
 *
 * @mixin \Eloquent
 */
class ServiceEntryIssue extends Model
{
    /**
     * @return BelongsTo<VehicleServiceEntry, $this>
     */
    public function serviceEntry(): BelongsTo
    {
        return $this->belongsTo(VehicleServiceEntry::class, 'vehicle_service_entry_id');
    }

    /**
     * @return BelongsTo<VehicleIssue, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(VehicleIssue::class, 'vehicle_issue_id');
    }
}
