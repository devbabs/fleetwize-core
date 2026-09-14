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
        Schema::table('driver_check_ins', function (Blueprint $table) {
            //
            $table->foreignId('vehicle_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('status', ['pass', 'fail'])->nullable();

            $table->unsignedInteger('odometer')->nullable();

            $table->unsignedTinyInteger('fuel_percentage')
                ->nullable()
                ->comment('0 - 100');

            $table->text('notes')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('driver_check_ins', function (Blueprint $table) {
            //
        });
    }
};
