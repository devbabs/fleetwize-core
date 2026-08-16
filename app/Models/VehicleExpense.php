<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $company_id
 * @property int $vehicle_id
 * @property int|null $vendor_id
 * @property int|null $reported_by
 * @property string $expense_type
 * @property numeric $amount
 * @property bool $is_recurring
 * @property CarbonImmutable $date
 * @property CarbonImmutable|null $recurring_end_date
 * @property string|null $recurring_frequency
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Company $company
 * @property-read User|null $reporter
 * @property-read Vehicle|null $vehicle
 * @property-read Vendor|null $vendor
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleExpense newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleExpense newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleExpense query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleExpense whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleExpense whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleExpense whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleExpense whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleExpense whereExpenseType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleExpense whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleExpense whereIsRecurring($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleExpense whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleExpense whereRecurringEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleExpense whereRecurringFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleExpense whereReportedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleExpense whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleExpense whereVehicleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleExpense whereVendorId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['expense_type', 'amount', 'is_recurring', 'date', 'recurring_end_date', 'recurring_frequency', 'notes'])]
class VehicleExpense extends Model
{
    protected function casts(): array
    {
        return [
            'is_recurring' => 'boolean',
            'date' => 'date',
            'recurring_end_date' => 'date',
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
}
