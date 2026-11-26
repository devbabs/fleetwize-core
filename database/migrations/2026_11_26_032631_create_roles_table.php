<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add module grouping + description to permissions for the React checkbox matrix
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('module', 60)->default('general')->after('slug')->index();
            $table->string('description')->nullable()->after('module');
        });

        // 2. Scope roles to companies and make vehicle-assignment fluid (no hardcoded 'driver' slug check)
        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->string('description')->nullable()->after('slug');
            $table->boolean('is_system')->default(false)->after('description'); // protects Company Admin from deletion
            $table->boolean('can_be_assigned_vehicle')->default(false)->after('is_system');

            // Drop global unique index on slug/name (check exact index name in published AccessTree migration)
            // $table->dropUnique('roles_slug_unique');
            $table->unique(['company_id', 'slug'], 'roles_company_slug_unique');
        });

        // 3. Free company_users.role from strict ENUM and link directly to roles.id
        DB::statement("ALTER TABLE company_users MODIFY COLUMN role VARCHAR(100) NULL DEFAULT NULL");

        Schema::table('company_users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('role')->constrained('roles')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_company_slug_unique');
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn(['description', 'is_system', 'can_be_assigned_vehicle']);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn(['module', 'description']);
        });
    }
};
