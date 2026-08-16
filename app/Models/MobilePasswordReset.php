<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilePasswordReset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilePasswordReset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilePasswordReset query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilePasswordReset whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilePasswordReset whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilePasswordReset whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilePasswordReset whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilePasswordReset whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MobilePasswordReset whereUserId($value)
 *
 * @mixin \Eloquent
 */
class MobilePasswordReset extends Model
{
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
