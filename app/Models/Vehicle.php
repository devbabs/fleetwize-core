<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $company_id
 * @property int|null $user_id
 * @property int|null $agent_id
 * @property string|null $name
 * @property string $category
 * @property string|null $vin
 * @property string|null $license_plate
 * @property string|null $make
 * @property string|null $model
 * @property string|null $year
 * @property string|null $vehicle
 * @property float $mileage
 * @property bool $is_owned
 * @property bool $is_active
 * @property string|null $color
 * @property string|null $fuel_type
 * @property string|null $body_type
 * @property string|null $body_subtype
 * @property numeric|null $msrp
 * @property string|null $obd_device_id
 * @property string|null $obd_device_imei
 * @property string|null $tracker_phone_number
 * @property float|null $maintenance_limit_km
 * @property string $status
 * @property string|null $transmission_type
 * @property bool $business_critical
 * @property CarbonImmutable|null $warranty_expires_at
 * @property string|null $purchase_year
 * @property string|null $purchase_condition
 * @property CarbonImmutable|null $deleted_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Agent|null $agent
 * @property-read Collection<int, VehicleAlarm> $alarms
 * @property-read int|null $alarms_count
 * @property-read Collection<int, VehicleAssignment> $assignments
 * @property-read int|null $assignments_count
 * @property-read Company|null $company
 * @property-read Collection<int, VehicleDiagnosticReport> $diagnosticReports
 * @property-read int|null $diagnostic_reports_count
 * @property-read Collection<int, VehicleDocument> $documents
 * @property-read int|null $documents_count
 * @property-read Collection<int, VehicleExpense> $expenses
 * @property-read int|null $expenses_count
 * @property-read Collection<int, VehicleFault> $faults
 * @property-read int|null $faults_count
 * @property-read Collection<int, VehicleFuelEntry> $fuelEntries
 * @property-read int|null $fuel_entries_count
 * @property-read Collection<int, VehicleIssue> $issues
 * @property-read int|null $issues_count
 * @property-read Collection<int, VehicleMaintenanceRecord> $maintenanceRecords
 * @property-read int|null $maintenance_records_count
 * @property-read Collection<int, VehicleServiceEntry> $serviceEntries
 * @property-read int|null $service_entries_count
 * @property-read Collection<int, VehicleStaffAssignment> $staffAssignments
 * @property-read int|null $staff_assignments_count
 * @property-read VehicleTrackerState|null $trackerState
 * @property-read Collection<int, VehicleTrip> $trips
 * @property-read int|null $trips_count
 * @property-read User|null $user
 * @property-read Collection<int, Workshop> $workshops
 * @property-read int|null $workshops_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereAgentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereBodySubtype($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereBodyType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereBusinessCritical($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereFuelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereIsOwned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereLicensePlate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereMaintenanceLimitKm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereMake($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereMileage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereMsrp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereObdDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereObdDeviceImei($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle wherePurchaseCondition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle wherePurchaseYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereTrackerPhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereTransmissionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereVehicle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereVin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereWarrantyExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'name', 'category', 'vin', 'license_plate', 'make', 'model', 'year', 'vehicle',
    'mileage', 'is_owned', 'is_active', 'color', 'fuel_type', 'body_type', 'body_subtype', 'msrp',
    'obd_device_id', 'obd_device_imei', 'tracker_phone_number', 'maintenance_limit_km', 'status',
    'transmission_type', 'business_critical', 'warranty_expires_at', 'purchase_year', 'purchase_condition',
])]
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'mileage' => 'double',
            'is_owned' => 'boolean',
            'is_active' => 'boolean',
            'business_critical' => 'boolean',
            'warranty_expires_at' => 'datetime',
        ];
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Agent, $this>
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * @return HasMany<VehicleAlarm, $this>
     */
    public function alarms(): HasMany
    {
        return $this->hasMany(VehicleAlarm::class);
    }

    /**
     * @return HasMany<VehicleFault, $this>
     */
    public function faults(): HasMany
    {
        return $this->hasMany(VehicleFault::class);
    }

    /**
     * @return HasMany<VehicleTrip, $this>
     */
    public function trips(): HasMany
    {
        return $this->hasMany(VehicleTrip::class);
    }

    /**
     * @return HasOne<VehicleTrackerState, $this>
     */
    public function trackerState(): HasOne
    {
        return $this->hasOne(VehicleTrackerState::class);
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
     * @return HasMany<VehicleIssue, $this>
     */
    public function issues(): HasMany
    {
        return $this->hasMany(VehicleIssue::class);
    }

    /**
     * @return HasMany<VehicleMaintenanceRecord, $this>
     */
    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(VehicleMaintenanceRecord::class);
    }

    /**
     * @return HasMany<VehicleServiceEntry, $this>
     */
    public function serviceEntries(): HasMany
    {
        return $this->hasMany(VehicleServiceEntry::class);
    }

    /**
     * @return HasMany<VehicleDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(VehicleDocument::class);
    }

    /**
     * @return HasMany<VehicleDiagnosticReport, $this>
     */
    public function diagnosticReports(): HasMany
    {
        return $this->hasMany(VehicleDiagnosticReport::class);
    }

    /**
     * @return BelongsToMany<Workshop, $this>
     */
    public function workshops(): BelongsToMany
    {
        return $this->belongsToMany(Workshop::class, 'vehicle_workshops');
    }
}
