<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property int $company_id
 * @property string $role
 * @property bool $is_contact_person
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, VehicleAssignment> $assignments
 * @property-read int|null $assignments_count
 * @property-read Collection<int, DriverCheckIn> $checkIns
 * @property-read int|null $check_ins_count
 * @property-read Company $company
 * @property-read VehicleAssignment|null $currentAssignment
 * @property-read mixed $role_text
 * @property-read Collection<int, VehicleStaffAssignment> $staffAssignments
 * @property-read int|null $staff_assignments_count
 * @property-read User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyUser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyUser whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyUser whereIsContactPerson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyUser whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyUser whereUserId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['role', 'is_contact_person'])]
class CompanyUser extends Model
{
    protected function casts(): array
    {
        return [
            'is_contact_person' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return HasMany<VehicleAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(VehicleAssignment::class);
    }

    /**
     * @return HasMany<VehicleStaffAssignment, $this>
     */
    public function staffAssignments(): HasMany
    {
        return $this->hasMany(VehicleStaffAssignment::class);
    }

    /**
     * @return HasMany<DriverCheckIn, $this>
     */
    public function checkIns(): HasMany
    {
        return $this->hasMany(DriverCheckIn::class);
    }

    /**
     * @return HasOne<VehicleAssignment, $this>
     */
    public function currentAssignment(): HasOne
    {
        return $this->hasOne(VehicleAssignment::class)
            ->whereDate('start_date', '<=', now())
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', now()))
            ->latestOfMany();
    }

    /** @return Attribute<string, never> */
    protected function roleText(): Attribute
    {
        return Attribute::make(
            get: fn () => str($this->role)->headline()->toString(),
        );
    }
}
