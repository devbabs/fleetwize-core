<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('address')->nullable();
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('height')->nullable();
            $table->string('next_of_kin')->nullable();
            $table->string('next_of_kin_relationship')->nullable();
            $table->string('next_of_kin_phone')->nullable();
            $table->string('drivers_license_number')->nullable();
            $table->date('drivers_license_expiry')->nullable();
            $table->timestamps();
        });

        Schema::create('driver_check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_user_id')->constrained()->cascadeOnDelete();
            $table->enum('tyre', ['good', 'bad'])->nullable();
            $table->enum('vehicle_condition', ['good', 'bad'])->nullable();
            $table->enum('engine_oil', ['good', 'bad'])->nullable();
            $table->enum('water_level', ['good', 'bad'])->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_check_ins');
        Schema::dropIfExists('drivers');
    }
};
