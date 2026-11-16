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
            $table->integer('fuel_level_percent')->nullable()->after('fuel');
            $table->float('coolant_temp')->nullable()->after('engine_load');
            $table->tinyInteger('green_driving_event')->nullable()->after('hard_cornering_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_route_points', function (Blueprint $table) {
            //
            $table->dropColumn([
                'fuel_level_percent',
                'coolant_temp',
                'green_driving_event',
            ]);
        });
    }
};
