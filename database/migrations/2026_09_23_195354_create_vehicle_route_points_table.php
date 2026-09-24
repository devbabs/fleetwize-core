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
        Schema::create('vehicle_route_points', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();

            $table->foreignId('vehicle_trip_id')->nullable()->constrained('vehicle_trips')->nullOnDelete();

            $table->unsignedBigInteger('traccar_position_id')->unique();

            $table->unsignedBigInteger('traccar_device_id')->nullable();

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            $table->decimal('speed_kmh', 8, 2)->nullable();
            $table->decimal('course', 8, 2)->nullable();

            $table->boolean('ignition')->nullable();
            $table->boolean('motion')->nullable();

            $table->unsignedBigInteger('odometer')->nullable();

            $table->timestamp('fix_time');

            $table->timestamp('device_time')->nullable();
            $table->timestamp('server_time')->nullable();

            $table->json('raw_payload')->nullable();

            $table->index(['vehicle_id', 'fix_time']);
            $table->index(['vehicle_trip_id', 'fix_time']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_route_points');
    }
};
