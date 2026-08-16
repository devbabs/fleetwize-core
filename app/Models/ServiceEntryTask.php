<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $vehicle_service_entry_id
 * @property int $service_task_id
 * @property numeric $labor_price
 * @property numeric $parts_price
 * @property numeric $sub_total
 * @property string|null $comments
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read VehicleServiceEntry $serviceEntry
 * @property-read ServiceTask $serviceTask
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceEntryTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceEntryTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceEntryTask query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceEntryTask whereComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceEntryTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceEntryTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceEntryTask whereLaborPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceEntryTask wherePartsPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceEntryTask whereServiceTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceEntryTask whereSubTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceEntryTask whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceEntryTask whereVehicleServiceEntryId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['labor_price', 'parts_price', 'sub_total', 'comments'])]
class ServiceEntryTask extends Model
{
    /**
     * @return BelongsTo<VehicleServiceEntry, $this>
     */
    public function serviceEntry(): BelongsTo
    {
        return $this->belongsTo(VehicleServiceEntry::class, 'vehicle_service_entry_id');
    }

    /**
     * @return BelongsTo<ServiceTask, $this>
     */
    public function serviceTask(): BelongsTo
    {
        return $this->belongsTo(ServiceTask::class);
    }
}
