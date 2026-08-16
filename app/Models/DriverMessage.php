<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property string|null $image
 * @property string $title
 * @property string $content
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Company $company
 * @property-read Collection<int, DriverMessageRecipient> $recipients
 * @property-read int|null $recipients_count
 * @property-read User $sender
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverMessage whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverMessage whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverMessage whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverMessage whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverMessage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DriverMessage whereUserId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['image', 'title', 'content'])]
class DriverMessage extends Model
{
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
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<DriverMessageRecipient, $this>
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(DriverMessageRecipient::class);
    }
}
