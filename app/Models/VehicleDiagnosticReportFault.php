<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $vehicle_diagnostic_report_id
 * @property string $severity
 * @property string|null $error_code
 * @property array<array-key, mixed>|null $assembly_group
 * @property array<array-key, mixed>|null $part_category
 * @property array<array-key, mixed>|null $part_sub_category
 * @property string|null $description
 * @property string|null $remark
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read VehicleDiagnosticReport $report
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDiagnosticReportFault newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDiagnosticReportFault newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDiagnosticReportFault query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDiagnosticReportFault whereAssemblyGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDiagnosticReportFault whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDiagnosticReportFault whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDiagnosticReportFault whereErrorCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDiagnosticReportFault whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDiagnosticReportFault wherePartCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDiagnosticReportFault wherePartSubCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDiagnosticReportFault whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDiagnosticReportFault whereSeverity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDiagnosticReportFault whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VehicleDiagnosticReportFault whereVehicleDiagnosticReportId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['severity', 'error_code', 'assembly_group', 'part_category', 'part_sub_category', 'description', 'remark'])]
class VehicleDiagnosticReportFault extends Model
{
    protected function casts(): array
    {
        return [
            'assembly_group' => 'array',
            'part_category' => 'array',
            'part_sub_category' => 'array',
        ];
    }

    /**
     * @return BelongsTo<VehicleDiagnosticReport, $this>
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(VehicleDiagnosticReport::class, 'vehicle_diagnostic_report_id');
    }
}
