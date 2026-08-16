<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->after('id');
            $table->string('last_name')->after('first_name');
            $table->string('phone')->nullable()->unique()->after('email');
            $table->string('avatar')->nullable()->after('password');
            $table->boolean('admin')->default(false)->after('avatar');
            $table->foreignId('agent_id')->nullable()->after('admin')->constrained()->nullOnDelete();
            $table->string('address')->nullable()->after('agent_id');
            $table->foreignId('country_id')->nullable()->after('address')->constrained()->nullOnDelete();
            $table->foreignId('state_id')->nullable()->after('country_id')->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->after('state_id')->constrained()->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('city_id');
            $table->dropConstrainedForeignId('state_id');
            $table->dropConstrainedForeignId('country_id');
            $table->dropColumn('address');
            $table->dropConstrainedForeignId('agent_id');
            $table->dropUnique(['phone']);
            $table->dropColumn(['first_name', 'last_name', 'phone', 'avatar', 'admin']);
        });
    }
};
