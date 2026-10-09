<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Schema::table('vehicle_trips', function (Blueprint $table) {
        //     //
        //     $table->dropUnique('unique_vehicle_trip_window');    
        //     // Add the 2-column index
        //     $table->unique(['vehicle_id', 'start_time'], 'unique_vehicle_trip_start');
        // });
        // Schema::table('vehicle_trips', function (Blueprint $table) {
        //     $indexes = collect(DB::select('SHOW INDEX FROM vehicle_trips'))
        //         ->pluck('Key_name')
        //         ->unique()
        //         ->toArray();

        //     if (in_array('unique_vehicle_trip_window', $indexes)) {
        //         $table->dropUnique('unique_vehicle_trip_window');
        //     }

        //     if (!in_array('unique_vehicle_trip_start', $indexes)) {
        //         $table->unique(
        //             ['vehicle_id', 'start_time'],
        //             'unique_vehicle_trip_start'
        //         );
        //     }
        // });
        Schema::table('vehicle_trips', function (Blueprint $table) {
            $table->dropUnique('vehicle_trip_unique');

            $table->unique(
                ['vehicle_id', 'start_time'],
                'unique_vehicle_trip_start'
            );
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
