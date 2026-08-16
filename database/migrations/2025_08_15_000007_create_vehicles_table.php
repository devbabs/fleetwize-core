<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('category')->default('car');
            $table->string('vin')->nullable();
            $table->string('license_plate')->nullable();
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->string('year')->nullable();
            $table->string('vehicle')->nullable();
            $table->double('mileage')->default(0);
            $table->boolean('is_owned')->default(true);
            $table->boolean('is_active')->default(true);
            $table->string('color')->nullable();
            $table->string('fuel_type')->nullable();
            $table->string('body_type')->nullable();
            $table->string('body_subtype')->nullable();
            $table->decimal('msrp', 12, 2)->nullable();
            $table->string('obd_device_id')->nullable();
            $table->string('obd_device_imei')->nullable()->unique();
            $table->string('tracker_phone_number')->nullable();
            $table->double('maintenance_limit_km')->nullable();
            $table->enum('status', ['active', 'maintenance'])->default('active');
            $table->string('transmission_type')->nullable();
            $table->boolean('business_critical')->default(false);
            $table->timestamp('warranty_expires_at')->nullable();
            $table->string('purchase_year')->nullable();
            $table->enum('purchase_condition', ['new', 'used', 'pre-owned'])->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
