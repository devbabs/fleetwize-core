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
        //
        DB::statement("ALTER TABLE company_users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'driver'");

        // 2. Migrate existing non-standard roles to the new standard fleet roles
        DB::table('company_users')->where('role', 'supervisor')->update(['role' => 'branch-manager']);
        DB::table('company_users')->where('role', 'staff')->update(['role' => 'fleet-manager']);
        DB::table('company_users')->where('role', 'vehicle-operator')->update(['role' => 'driver']);
        DB::table('company_users')->where('role', 'workshop-admin')->update(['role' => 'maintenance-manager']);

        // 3. Apply the new strict ENUM definition
        DB::statement("
            ALTER TABLE company_users 
            MODIFY COLUMN role ENUM(
                'admin',
                'fleet-manager',
                'dispatcher',
                'branch-manager',
                'control-room-officer',
                'driver',
                'dispatch-rider',
                'maintenance-manager',
                'mechanic',
                'tracker-installer'
            ) NOT NULL DEFAULT 'driver'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        DB::statement("ALTER TABLE company_users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'driver'");

        DB::table('company_users')->whereIn('role', ['branch-manager'])->update(['role' => 'supervisor']);
        DB::table('company_users')->whereIn('role', ['fleet-manager', 'dispatcher', 'control-room-officer', 'tracker-installer'])->update(['role' => 'staff']);
        DB::table('company_users')->whereIn('role', ['dispatch-rider'])->update(['role' => 'driver']);
        DB::table('company_users')->whereIn('role', ['maintenance-manager', 'mechanic'])->update(['role' => 'workshop-admin']);

        DB::statement("
            ALTER TABLE company_users 
            MODIFY COLUMN role ENUM(
                'admin',
                'supervisor',
                'staff',
                'vehicle-operator',
                'workshop-admin',
                'driver'
            ) NOT NULL DEFAULT 'driver'
        ");
    }
};
