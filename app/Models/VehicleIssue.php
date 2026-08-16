<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $company_id
 * @property int $vehicle_id
 * @property int|null $vendor_id
 * @property int|null $reported_by
 * @property int|null $assigned_to
 * @property string $priority
 * @property CarbonImmutable|null $reported_at
 * @property CarbonImmutable|null $overdue_date
 * @property string $summary
 * @property string|null $description
 * @property string $status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $assignee
 * @property-read Company $company
 * @property-read Collection<int, VehicleIssueImage> $images
 * @property-read int|null $images_count
 * @property-read User|null $reporter
 * @property-read Vehicle|null $vehicle
 * @property-read Vendor|null $vendor
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssue query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssue whereAssignedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssue whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssue whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssue whereOverdueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssue wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssue whereReportedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssue whereReportedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssue whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssue whereSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssue whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssue whereVehicleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleIssue whereVendorId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['priority', 'reported_at', 'overdue_date', 'summary', 'description', 'status'])]
class VehicleIssue extends Model
{
    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'overdue_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return HasMany<VehicleIssueImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(VehicleIssueImage::class);
    }
}
