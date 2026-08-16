<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $company_id
 * @property string $category
 * @property string|null $reference
 * @property string $file_path
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Company $company
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulkUpload newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulkUpload newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulkUpload query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulkUpload whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulkUpload whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulkUpload whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulkUpload whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulkUpload whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulkUpload whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulkUpload whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BulkUpload whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class BulkUpload extends Model
{
    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
