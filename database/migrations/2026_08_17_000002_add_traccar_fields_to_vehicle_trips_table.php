<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_trips', function (Blueprint $table) {
            $table->double('start_odometer')->nullable()->after('end_time');
            $table->double('end_odometer')->nullable()->after('start_odometer');
            $table->decimal('start_latitude', 20, 17)->nullable()->after('end_odometer');
            $table->decimal('start_longitude', 20, 17)->nullable()->after('start_latitude');
            $table->decimal('end_latitude', 20, 17)->nullable()->after('start_longitude');
            $table->decimal('end_longitude', 20, 17)->nullable()->after('end_latitude');
            $table->string('start_address')->nullable()->after('end_longitude');
            $table->string('end_address')->nullable()->after('start_address');
            $table->string('driver_unique_id')->nullable()->after('end_address');
            $table->string('driver_name')->nullable()->after('driver_unique_id');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_trips', function (Blueprint $table) {
            $table->dropColumn([
                'start_odometer',
                'end_odometer',
                'start_latitude',
                'start_longitude',
                'end_latitude',
                'end_longitude',
                'start_address',
                'end_address',
                'driver_unique_id',
                'driver_name',
            ]);
        });
    }
};
