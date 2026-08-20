<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geofences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('shape');
            $table->decimal('center_latitude', 20, 17)->nullable();
            $table->decimal('center_longitude', 20, 17)->nullable();
            $table->double('radius_meters')->nullable();
            $table->json('polygon')->nullable();
            $table->unsignedInteger('traccar_geofence_id')->nullable();
            $table->string('color')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geofences');
    }
};
