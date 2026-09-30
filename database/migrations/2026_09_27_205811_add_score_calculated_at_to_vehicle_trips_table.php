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
            $table->timestamp('score_calculated_at')->nullable();
            $table->foreignId('company_user_id')->nullable();

            $table->decimal('score', 5, 2)->nullable();

            $table->decimal('safety_score', 5, 2)->nullable();
            $table->decimal('efficiency_score', 5, 2)->nullable();

            $table->string('grade')->nullable();

            $table->integer('overspeed_events')->default(0);
            $table->integer('severe_overspeed_events')->default(0);

            $table->integer('harsh_acceleration_events')->default(0);
            $table->integer('harsh_braking_events')->default(0);
            $table->integer('harsh_cornering_events')->default(0);

            $table->integer('idle_minutes')->default(0);

            $table->decimal('trip_distance_km', 10, 2)->nullable();
            $table->integer('trip_duration_seconds')->nullable();

            $table->decimal('average_speed', 8, 2)->nullable();
            $table->decimal('max_speed', 8, 2)->nullable();

            $table->decimal('fuel_efficiency_score', 5, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_trips', function (Blueprint $table) {
            //
        });
    }
};
