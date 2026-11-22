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
            if (! Schema::hasColumn('driver_scorecards', 'fuel_consumed')) {
                $table->decimal('fuel_consumed', 8, 2)->nullable()->after('distance_km');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('driver_scorecards', function (Blueprint $table) {
            //
            $table->dropColumn('fuel_consumed');
        });
    }
};
