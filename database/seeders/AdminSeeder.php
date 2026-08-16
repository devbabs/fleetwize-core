<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds the one super-admin account. This is the only way into the web
 * dashboard on a fresh install — there is no public self-registration.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => config('fleetwize.admin.email')],
            [
                'first_name' => config('fleetwize.admin.first_name'),
                'last_name' => config('fleetwize.admin.last_name'),
                'password' => config('fleetwize.admin.password'),
                'admin' => true,
                'email_verified_at' => now(),
            ],
        );

        $this->command->info("Super-admin ready: {$user->email}");
    }
}
