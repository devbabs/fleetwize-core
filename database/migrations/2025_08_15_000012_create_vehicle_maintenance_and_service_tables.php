<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->timestamp('maintained_at');
            $table->timestamps();
        });

        Schema::create('vehicle_service_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->double('meter_reading')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('reference')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();
        });

        Schema::create('service_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('outlet_id')->nullable();
            $table->string('outlet_name')->nullable();
            $table->string('category_id')->nullable();
            $table->string('category_name')->nullable();
            $table->string('sub_category_id')->nullable();
            $table->string('sub_category_name')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('service_entry_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_service_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_task_id')->constrained()->cascadeOnDelete();
            $table->decimal('labor_price', 12, 2)->default(0);
            $table->decimal('parts_price', 12, 2)->default(0);
            $table->decimal('sub_total', 12, 2)->default(0);
            $table->text('comments')->nullable();
            $table->timestamps();
        });

        Schema::create('service_entry_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_service_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_issue_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_entry_issues');
        Schema::dropIfExists('service_entry_tasks');
        Schema::dropIfExists('service_tasks');
        Schema::dropIfExists('vehicle_service_entries');
        Schema::dropIfExists('vehicle_maintenance_records');
    }
};
