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
        Schema::table('maintenance_schedules', function (Blueprint $table) {
            $table->integer('engine_hours_interval')->nullable()->after('time_interval_days');
            $table->float('baseline_engine_hours')->nullable()->after('baseline_odometer_km');
        });

        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->float('engine_hours')->nullable()->after('odometer_km');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_schedules', function (Blueprint $table) {
            $table->dropColumn(['engine_hours_interval', 'baseline_engine_hours']);
        });

        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->dropColumn('engine_hours');
        });
    }
};
