<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $description
 * @property string|null $outlet_id
 * @property string|null $outlet_name
 * @property string|null $category_id
 * @property string|null $category_name
 * @property string|null $sub_category_id
 * @property string|null $sub_category_name
 * @property string|null $reason
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Company $company
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereCategoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereOutletId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereOutletName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereSubCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereSubCategoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['name', 'description', 'outlet_id', 'outlet_name', 'category_id', 'category_name', 'sub_category_id', 'sub_category_name', 'reason'])]
class ServiceTask extends Model
{
    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
