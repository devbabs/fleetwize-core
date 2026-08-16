<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $vehicle_id
 * @property int $document_id
 * @property string|null $document_title
 * @property CarbonImmutable|null $last_renewed_at
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Document $document
 * @property-read mixed $expiry_status
 * @property-read Vehicle|null $vehicle
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDocument expiresSoon()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDocument newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDocument newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDocument query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDocument whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDocument whereDocumentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDocument whereDocumentTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDocument whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDocument whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDocument whereLastRenewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDocument whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDocument whereVehicleId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['document_title', 'last_renewed_at', 'expires_at'])]
class VehicleDocument extends Model
{
    protected function casts(): array
    {
        return [
            'last_renewed_at' => 'date',
            'expires_at' => 'date',
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
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /** @return Attribute<string|null, never> */
    protected function expiryStatus(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->expires_at) {
                    return null;
                }

                if ($this->expires_at->isPast()) {
                    return 'expired';
                }

                return $this->expires_at->lte(now()->addDays(30)) ? 'soon' : 'valid';
            },
        );
    }

    /**
     * @param  Builder<VehicleDocument>  $query
     * @return Builder<VehicleDocument>
     */
    public function scopeExpiresSoon(Builder $query): Builder
    {
        return $query->whereBetween('expires_at', [now(), now()->addDays(30)]);
    }
}
