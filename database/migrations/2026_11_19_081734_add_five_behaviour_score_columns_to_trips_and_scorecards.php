<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $scoreColumns = [
            'harsh_braking_score',
            'harsh_acceleration_score',
            'speeding_score',
            'hard_cornering_score',
            'idling_score',
        ];

        Schema::table('vehicle_trips', function (Blueprint $table) use ($scoreColumns) {
            foreach ($scoreColumns as $column) {
                if (! Schema::hasColumn('vehicle_trips', $column)) {
                    $table->decimal($column, 5, 2)->nullable()->after('grade');
                }
            }
        });

        Schema::table('driver_scorecards', function (Blueprint $table) use ($scoreColumns) {
            foreach ($scoreColumns as $column) {
                if (! Schema::hasColumn('driver_scorecards', $column)) {
                    $table->decimal($column, 5, 2)->nullable()->after('grade');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columnsToDrop = [
            'harsh_braking_score',
            'harsh_acceleration_score',
            'hard_cornering_score',
            'idling_score',
        ];

        Schema::table('vehicle_trips', function (Blueprint $table) use ($columnsToDrop) {
            $existing = array_filter(
                $columnsToDrop,
                fn (string $column) => Schema::hasColumn('vehicle_trips', $column)
            );

            if (! empty($existing)) {
                $table->dropColumn($existing);
            }
        });

        Schema::table('driver_scorecards', function (Blueprint $table) use ($columnsToDrop) {
            $existing = array_filter(
                $columnsToDrop,
                fn (string $column) => Schema::hasColumn('driver_scorecards', $column)
            );

            if (! empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};
