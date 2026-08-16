<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $vehicle_issue_id
 * @property string|null $content
 * @property string $disk
 * @property string|null $cdn_url
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read VehicleIssue $issue
 * @property-read mixed $url
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssueImage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssueImage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssueImage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssueImage whereCdnUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssueImage whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssueImage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssueImage whereDisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssueImage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssueImage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssueImage whereVehicleIssueId($value)
 *
 * @mixin \Eloquent
 */
class VehicleIssueImage extends Model
{
    /**
     * @return BelongsTo<VehicleIssue, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(VehicleIssue::class, 'vehicle_issue_id');
    }

    /** @return Attribute<string, never> */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->cdn_url ?? Storage::disk($this->disk)->url((string) $this->content),
        );
    }
}
