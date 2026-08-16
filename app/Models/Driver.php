<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $address
 * @property CarbonImmutable|null $dob
 * @property string|null $gender
 * @property string|null $height
 * @property string|null $next_of_kin
 * @property string|null $next_of_kin_relationship
 * @property string|null $next_of_kin_phone
 * @property string|null $drivers_license_number
 * @property CarbonImmutable|null $drivers_license_expiry
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereDob($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereDriversLicenseExpiry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereDriversLicenseNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereNextOfKin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereNextOfKinPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereNextOfKinRelationship($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Driver whereUserId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['address', 'dob', 'gender', 'height', 'next_of_kin', 'next_of_kin_relationship', 'next_of_kin_phone', 'drivers_license_number', 'drivers_license_expiry'])]
class Driver extends Model
{
    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'drivers_license_expiry' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
