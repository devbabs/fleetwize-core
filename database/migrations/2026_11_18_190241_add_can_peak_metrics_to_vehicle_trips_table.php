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
        Schema::table('vehicle_trips', function (Blueprint $table) {
            //
            if (! Schema::hasColumn('vehicle_trips', 'max_rpm')) {
                $table->unsignedInteger('max_rpm')->nullable()->after('max_speed_km_per_hr');
            }
            if (! Schema::hasColumn('vehicle_trips', 'max_engine_load')) {
                $table->decimal('max_engine_load', 5, 2)->nullable()->after('max_rpm');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_trips', function (Blueprint $table) {
            //
            $table->dropColumn(['max_rpm', 'max_engine_load']);
        });
    }
};
