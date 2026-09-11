<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/** Creates the system administrator from ADMIN_EMAIL / ADMIN_NAME in .env (login is by OTP, no password needed). */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = mb_strtolower((string) env('ADMIN_EMAIL', 'admin@example.com'));
        [$first, $last] = array_pad(explode(' ', trim((string) env('ADMIN_NAME', 'Admin')), 2), 2, null);

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'first_name' => $first ?: 'Admin',
                'last_name' => $last,
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command?->info("Admin user: {$email}");
    }
}
