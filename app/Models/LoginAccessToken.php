<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAccessToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAccessToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAccessToken query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAccessToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAccessToken whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAccessToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAccessToken whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAccessToken whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAccessToken whereUserId($value)
 *
 * @mixin \Eloquent
 */
class LoginAccessToken extends Model
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

    public function regenerate(): string
    {
        $this->update([
            'token' => $token = (string) random_int(100000, 999999),
            'expires_at' => Carbon::now()->addHour(),
        ]);

        return $token;
    }

    public function revoke(): void
    {
        $this->update(['expires_at' => Carbon::now()->subMinute()]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
