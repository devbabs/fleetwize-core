<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_tracker_states', function (Blueprint $table) {
            $table->unsignedInteger('engine_rpm')->nullable()->after('fuel_level');
            $table->double('engine_load')->nullable()->after('engine_rpm');
            $table->double('obd_speed')->nullable()->after('engine_load');
            $table->boolean('is_moving')->nullable()->after('obd_speed');
            $table->double('battery_level')->nullable()->after('is_moving');
            $table->unsignedTinyInteger('satellite_count')->nullable()->after('battery_level');
            $table->unsignedTinyInteger('signal_strength')->nullable()->after('satellite_count');
            $table->double('engine_hours')->nullable()->after('signal_strength');
            $table->boolean('is_blocked')->nullable()->after('engine_hours');
            $table->boolean('is_charging')->nullable()->after('is_blocked');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_tracker_states', function (Blueprint $table) {
            $table->dropColumn([
                'engine_rpm',
                'engine_load',
                'obd_speed',
                'is_moving',
                'battery_level',
                'satellite_count',
                'signal_strength',
                'engine_hours',
                'is_blocked',
                'is_charging',
            ]);
        });
    }
};
