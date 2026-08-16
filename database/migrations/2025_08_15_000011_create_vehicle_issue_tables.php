<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->timestamp('reported_at')->nullable();
            $table->date('overdue_date')->nullable();
            $table->string('summary');
            $table->text('description')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();
        });

        Schema::create('vehicle_issue_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_issue_id')->constrained()->cascadeOnDelete();
            $table->text('content')->nullable();
            $table->string('disk')->default('local');
            $table->string('cdn_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_issue_images');
        Schema::dropIfExists('vehicle_issues');
    }
};
