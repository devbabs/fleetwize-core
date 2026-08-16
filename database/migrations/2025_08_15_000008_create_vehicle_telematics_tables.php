<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_alarms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('obd_device_id')->nullable();
            $table->string('alarm_id')->nullable();
            $table->string('alarm_type')->nullable();
            $table->string('alarm_description')->nullable();
            $table->text('description')->nullable();
            $table->decimal('latitude', 20, 17)->nullable();
            $table->decimal('longitude', 20, 17)->nullable();
            $table->timestamp('gps_time')->nullable();
            $table->timestamps();
        });

        Schema::create('vehicle_faults', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('obd_device_id')->nullable();
            $table->string('obd_code')->nullable();
            $table->text('obd_description')->nullable();
            $table->text('obd_description_en')->nullable();
            $table->timestamp('log_time')->nullable();
            $table->timestamp('cleared_at')->nullable();
            $table->text('meaning')->nullable();
            $table->json('common_causes')->nullable();
            $table->json('symptoms')->nullable();
            $table->json('possible_fixes')->nullable();
            $table->text('note')->nullable();
            $table->unsignedTinyInteger('severity')->nullable();
            $table->timestamps();
        });

        Schema::create('vehicle_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('obd_device_id')->nullable();
            $table->double('fuel_consumed')->nullable();
            $table->double('distance_km')->nullable();
            $table->double('max_speed_km_per_hr')->nullable();
            $table->unsignedInteger('brake_times')->nullable();
            $table->unsignedInteger('emergency_brake_times')->nullable();
            $table->unsignedInteger('speed_up_times')->nullable();
            $table->unsignedInteger('emergency_speed_up_times')->nullable();
            $table->double('average_speed_km_per_hr')->nullable();
            $table->double('max_temperature_celsius')->nullable();
            $table->unsignedInteger('max_engine_rpm')->nullable();
            $table->string('js_type')->nullable();
            $table->unsignedInteger('drive_time_seconds')->nullable();
            $table->unsignedInteger('idling_time_seconds')->nullable();
            $table->date('trip_date')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->timestamps();
        });

        // Latest-known live state per vehicle, upserted from the Traccar middleware.
        Schema::create('vehicle_tracker_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 20, 17)->nullable();
            $table->decimal('longitude', 20, 17)->nullable();
            $table->double('speed')->nullable();
            $table->double('heading')->nullable();
            $table->boolean('ignition_on')->nullable();
            $table->double('battery_voltage')->nullable();
            $table->double('fuel_level')->nullable();
            $table->timestamp('reported_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_tracker_states');
        Schema::dropIfExists('vehicle_trips');
        Schema::dropIfExists('vehicle_faults');
        Schema::dropIfExists('vehicle_alarms');
    }
};
