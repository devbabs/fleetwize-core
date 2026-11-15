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
        Schema::table('vehicle_tracker_states', function (Blueprint $table) {
            //
            $table->float('tracker_battery_voltage')->nullable()->after('battery_voltage');
            $table->integer('fuel_level_percent')->nullable()->after('fuel_level');
            $table->float('fuel_consumed')->nullable()->after('fuel_level_percent');
            $table->float('coolant_temp')->nullable()->after('engine_load');
            $table->integer('dtc_count')->nullable()->after('hard_deceleration_count');
            $table->string('dtc_codes')->nullable()->after('dtc_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_tracker_states', function (Blueprint $table) {
            //
            $table->dropColumn([
                'tracker_battery_voltage',
                'fuel_level_percent',
                'fuel_consumed',
                'coolant_temp',
                'dtc_count',
                'dtc_codes',
            ]);
        });
    }
};
