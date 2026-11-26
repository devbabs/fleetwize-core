<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        // 1. Create the global permissions catalog
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('module', 60)->default('general')->index();
            $table->string('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        // 2. Create company-scoped roles
        Schema::create('roles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->string('description')->nullable();

            $table->boolean('is_system')->default(false);
            $table->boolean('can_be_assigned_vehicle')->default(false);

            $table->timestamps();

            $table->unique(
                ['company_id', 'slug'],
                'roles_company_slug_unique'
            );
        });

        // 3. Link roles to permissions (many-to-many)
        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();

            $table->foreignId('permission_id')
                ->constrained('permissions')
                ->cascadeOnDelete();

            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();

            $table->unique(['permission_id', 'role_id']);
        });

        // 4. Update company_users
        DB::statement("
            ALTER TABLE company_users
            MODIFY COLUMN role VARCHAR(100) NULL DEFAULT NULL
        ");

        Schema::table('company_users', function (Blueprint $table) {
            $table->foreignId('role_id')
                ->nullable()
                ->after('role')
                ->constrained('roles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('company_users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });

        // Restore the original role column type only if
        // you know its original definition.
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
};
