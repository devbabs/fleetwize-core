<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('address')->nullable();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->string('postal_code')->nullable();
            $table->foreignId('contact_person_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('vehicle_workshops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workshop_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['vehicle_id', 'workshop_id']);
        });

        Schema::create('vehicle_diagnostic_reports', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workshop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('vehicle_diagnostic_report_faults', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_diagnostic_report_id')
                ->constrained(indexName: 'vehicle_diagnostic_report_faults_report_id_foreign')
                ->cascadeOnDelete();
            $table->enum('severity', ['low', 'medium', 'major', 'critical'])->default('low');
            $table->string('error_code')->nullable();
            $table->json('assembly_group')->nullable();
            $table->json('part_category')->nullable();
            $table->json('part_sub_category')->nullable();
            $table->text('description')->nullable();
            $table->text('remark')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_diagnostic_report_faults');
        Schema::dropIfExists('vehicle_diagnostic_reports');
        Schema::dropIfExists('vehicle_workshops');
        Schema::dropIfExists('workshops');
    }
};
