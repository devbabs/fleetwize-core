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
        Schema::create('document_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_document_id')->constrained()->cascadeOnDelete();
            $table->date('expires_at');
            $table->string('milestone', 20); // 30_days, 14_days, 7_days, 3_days, expired
            $table->timestamp('sent_at');

            $table->unique(
                ['vehicle_document_id', 'expires_at', 'milestone'],
                'doc_reminder_unique'
            );
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_reminder_logs');
    }
};
