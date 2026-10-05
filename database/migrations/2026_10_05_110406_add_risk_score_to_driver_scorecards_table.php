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
        Schema::table('driver_scorecards', function (Blueprint $table) {
            //
            $table->decimal('risk_score', 5, 2)->default(100);
            $table->decimal('speeding_score', 5, 2)->default(100);
            $table->decimal('eco_score', 5, 2)->default(100);
            $table->decimal('fatigue_score', 5, 2)->default(100);
            $table->decimal('distraction_score', 5, 2)->default(100);

            $table->json('score_breakdown')->nullable();

            $table->unsignedInteger('high_engine_load_events')->default(0);
            $table->unsignedInteger('fatigue_events')->default(0);
            $table->unsignedInteger('overspeed_duration_seconds')->default(0);
            $table->unsignedInteger('continuous_driving_seconds')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('driver_scorecards', function (Blueprint $table) {
            //
        });
    }
};
