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
            $table->string('unique_id')->unique()->nullable();
            $table->string('device_status')->nullable();
            $table->string('protocol')->nullable();

            $table->decimal('altitude', 10, 2)->nullable();

            $table->boolean('gps_valid')->nullable();

            $table->timestamp('device_time')->nullable();
            $table->timestamp('server_time')->nullable();

            $table->unsignedBigInteger('odometer')->nullable();
            $table->unsignedBigInteger('obd_odometer')->nullable();

            $table->decimal('total_distance', 15, 3)->nullable();

            $table->unsignedInteger('hard_cornering_count')->nullable();
            $table->unsignedInteger('hard_acceleration_count')->nullable();
            $table->unsignedInteger('hard_deceleration_count')->nullable(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_tracker_states_tables', function (Blueprint $table) {
            //
            $table->dropColumn('unique_id');
            $table->dropColumn('device_status');
            $table->dropColumn('protocol');
            $table->dropColumn('altitude');
            $table->dropColumn('gps_valid');
            $table->dropColumn('device_time');
            $table->dropColumn('server_time');
            $table->dropColumn('odometer');
            $table->dropColumn('total_distance');
            $table->dropColumn('hard_cornering_count');
            $table->dropColumn('hard_acceleration_count');
            $table->dropColumn('hard_deceleration_count');
        });
    }
};
