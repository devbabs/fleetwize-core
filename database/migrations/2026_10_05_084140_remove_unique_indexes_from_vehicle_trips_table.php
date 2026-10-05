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
            $table->dropUnique('vehicle_trips_start_position_id_unique');
            $table->dropUnique('vehicle_trips_end_position_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_trips', function (Blueprint $table) {
            //
            $table->unique('start_position_id');
            $table->unique('end_position_id');
        });
    }
};
