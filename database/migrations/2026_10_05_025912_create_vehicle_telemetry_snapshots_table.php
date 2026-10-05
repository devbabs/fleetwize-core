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
        Schema::create('vehicle_telemetry_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id');

            $table->decimal('speed_kph', 8, 2)->nullable();
            $table->decimal('obd_speed_kph', 8, 2)->nullable();

            $table->integer('hard_acceleration_count')->default(0);
            $table->integer('hard_deceleration_count')->default(0);
            $table->integer('hard_cornering_count')->default(0);

            $table->integer('engine_load')->nullable();

            $table->boolean('ignition')->default(false);

            $table->timestamp('recorded_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_telemetry_snapshots');
    }
};
