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
 * @property string $name
 * @property string|null $phone
 * @property string|null $email
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Company $company
 * @property-read Collection<int, VehicleExpense> $expenses
 * @property-read int|null $expenses_count
 * @property-read Collection<int, VehicleFuelEntry> $fuelEntries
 * @property-read int|null $fuel_entries_count
 * @property-read Collection<int, VehicleIssue> $issues
 * @property-read int|null $issues_count
 * @property-read Collection<int, VehicleServiceEntry> $serviceEntries
 * @property-read int|null $service_entries_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vendor whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['name', 'phone', 'email'])]
class Vendor extends Model
{
    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return HasMany<VehicleExpense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(VehicleExpense::class);
    }

    /**
     * @return HasMany<VehicleFuelEntry, $this>
     */
    public function fuelEntries(): HasMany
    {
        return $this->hasMany(VehicleFuelEntry::class);
    }

    /**
     * @return HasMany<VehicleServiceEntry, $this>
     */
    public function serviceEntries(): HasMany
    {
        return $this->hasMany(VehicleServiceEntry::class);
    }

    /**
     * @return HasMany<VehicleIssue, $this>
     */
    public function issues(): HasMany
    {
        return $this->hasMany(VehicleIssue::class);
    }
}
