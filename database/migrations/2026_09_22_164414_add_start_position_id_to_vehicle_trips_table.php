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
            $table->string('start_position_id')->unique()->nullable()->after('start_time');
            $table->string('end_position_id')->unique()->nullable()->after('end_time');
            $table->unsignedInteger('duration_seconds')->nullable()->after('end_position_id');

            $table->unique([
                'vehicle_id',
                'start_time',
                'end_time',
            ], 'vehicle_trip_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_trips', function (Blueprint $table) {
            //
            $table->dropColumn(['start_position_id', 'end_position_id']);
        });
    }
};
