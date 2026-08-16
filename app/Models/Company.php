<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $website
 * @property int|null $country_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, BulkUpload> $bulkUploads
 * @property-read int|null $bulk_uploads_count
 * @property-read Collection<int, CompanyUser> $companyUsers
 * @property-read int|null $company_users_count
 * @property-read Country|null $country
 * @property-read Collection<int, DriverCheckIn> $driverCheckIns
 * @property-read int|null $driver_check_ins_count
 * @property-read Collection<int, DriverMessage> $driverMessages
 * @property-read int|null $driver_messages_count
 * @property-read Collection<int, VehicleExpense> $expenses
 * @property-read int|null $expenses_count
 * @property-read Collection<int, VehicleFuelEntry> $fuelEntries
 * @property-read int|null $fuel_entries_count
 * @property-read Collection<int, VehicleIssue> $issues
 * @property-read int|null $issues_count
 * @property-read Collection<int, VehicleServiceEntry> $serviceEntries
 * @property-read int|null $service_entries_count
 * @property-read Collection<int, ServiceTask> $serviceTasks
 * @property-read int|null $service_tasks_count
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 * @property-read Collection<int, VehicleAssignment> $vehicleAssignments
 * @property-read int|null $vehicle_assignments_count
 * @property-read Collection<int, Vehicle> $vehicles
 * @property-read int|null $vehicles_count
 * @property-read Collection<int, Vendor> $vendors
 * @property-read int|null $vendors_count
 * @property-read Collection<int, Workshop> $workshops
 * @property-read int|null $workshops_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereWebsite($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['name', 'slug', 'email', 'phone', 'website', 'country_id'])]
class Company extends Model
{
    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return HasMany<CompanyUser, $this>
     */
    public function companyUsers(): HasMany
    {
        return $this->hasMany(CompanyUser::class);
    }

    /**
     * @return HasManyThrough<User, CompanyUser, $this>
     */
    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, CompanyUser::class);
    }

    /**
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * @return HasMany<Workshop, $this>
     */
    public function workshops(): HasMany
    {
        return $this->hasMany(Workshop::class);
    }

    /**
     * @return HasMany<Vendor, $this>
     */
    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
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
     * @return HasMany<ServiceTask, $this>
     */
    public function serviceTasks(): HasMany
    {
        return $this->hasMany(ServiceTask::class);
    }

    /**
     * @return HasMany<VehicleIssue, $this>
     */
    public function issues(): HasMany
    {
        return $this->hasMany(VehicleIssue::class);
    }

    /**
     * @return HasMany<VehicleAssignment, $this>
     */
    public function vehicleAssignments(): HasMany
    {
        return $this->hasMany(VehicleAssignment::class);
    }

    /**
     * @return HasManyThrough<DriverCheckIn, CompanyUser, $this>
     */
    public function driverCheckIns(): HasManyThrough
    {
        return $this->hasManyThrough(DriverCheckIn::class, CompanyUser::class);
    }

    /**
     * @return HasMany<DriverMessage, $this>
     */
    public function driverMessages(): HasMany
    {
        return $this->hasMany(DriverMessage::class);
    }

    /**
     * @return HasMany<BulkUpload, $this>
     */
    public function bulkUploads(): HasMany
    {
        return $this->hasMany(BulkUpload::class);
    }
}
