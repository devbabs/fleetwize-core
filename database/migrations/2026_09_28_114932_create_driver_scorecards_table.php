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
        Schema::create('driver_scorecards', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('period_type', [
                'daily',
                'weekly',
                'monthly',
            ]);

            $table->date('period_start');
            $table->date('period_end');

            $table->integer('trip_count')->default(0);

            $table->decimal('score', 5, 2)->default(0);
            $table->decimal('safety_score', 5, 2)->default(0);
            $table->decimal('efficiency_score', 5, 2)->default(0);

            $table->decimal('distance_km', 10, 2)->default(0);

            $table->integer('duration_seconds')->default(0);

            $table->integer('overspeed_events')->default(0);
            $table->integer('severe_overspeed_events')->default(0);

            $table->integer('harsh_acceleration_events')->default(0);
            $table->integer('harsh_braking_events')->default(0);
            $table->integer('harsh_cornering_events')->default(0);

            $table->integer('idle_minutes')->default(0);

            $table->string('grade')->nullable();

            $table->unique(
                ['company_user_id', 'period_type', 'period_start'],
                'driver_scorecard_period_unique'
            );

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_scorecards');
    }
};
