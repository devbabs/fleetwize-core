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
        Schema::table('company_users', function (Blueprint $table) {
            //
            $table->enum('role', ['admin', 'supervisor', 'staff', 'vehicle-operator', 'workshop-admin', 'driver'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_users', function (Blueprint $table) {
            //
        });
    }
};
