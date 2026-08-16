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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailVerification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailVerification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailVerification query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailVerification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailVerification whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailVerification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailVerification whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailVerification whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailVerification whereUserId($value)
 *
 * @mixin \Eloquent
 */
class EmailVerification extends Model
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
