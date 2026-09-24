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
        Schema::table('vehicle_route_points', function (Blueprint $table) {
            //
            Schema::table('vehicle_route_points', function (Blueprint $table) {
                $table->decimal('altitude', 10, 2)->nullable()->after('longitude');

                $table->decimal('accuracy', 10, 2)->nullable()->after('altitude');

                $table->boolean('valid')->nullable()->after('accuracy');

                $table->boolean('blocked')->nullable();

                $table->boolean('charge')->nullable();

                $table->bigInteger('hours')->nullable();

                $table->decimal('obd_speed_kmh', 10, 2)->nullable();

                $table->decimal('obd_odometer', 15, 2)->nullable();

                $table->decimal('fuel', 10, 2)->nullable();

                $table->decimal('fuel_consumption', 10, 2)->nullable();

                $table->decimal('distance', 15, 2)->nullable();

                $table->decimal('total_distance', 20, 4)->nullable();

                $table->integer('rpm')->nullable();

                $table->decimal('engine_load', 12, 2)->nullable();

                $table->decimal('power', 10, 3)->nullable();

                $table->decimal('battery', 10, 3)->nullable();

                $table->decimal('battery_level', 10, 2)->nullable();

                $table->integer('sat')->nullable();

                $table->integer('rssi')->nullable();

                $table->integer('hard_acceleration_count')->nullable();

                $table->integer('hard_deceleration_count')->nullable();

                $table->integer('hard_cornering_count')->nullable();

                $table->boolean('charge')->nullable();
                $table->bigInteger('hours')->nullable();
                $table->decimal('power', 10, 3)->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_route_points', function (Blueprint $table) {
            //
        });
    }
};
