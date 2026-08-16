<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $driver_message_id
 * @property int $user_id
 * @property bool $read
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read DriverMessage $message
 * @property-read User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverMessageRecipient newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverMessageRecipient newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverMessageRecipient query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverMessageRecipient whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverMessageRecipient whereDriverMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverMessageRecipient whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverMessageRecipient whereRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverMessageRecipient whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverMessageRecipient whereUserId($value)
 *
 * @mixin \Eloquent
 */
class DriverMessageRecipient extends Model
{
    protected function casts(): array
    {
        return [
            'read' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<DriverMessage, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(DriverMessage::class, 'driver_message_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
