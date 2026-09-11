<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminSeeder::class);

        // The placeholder memorial is for local work only; production content comes
        // from `php artisan endless:import`.
        if (! app()->environment('production')) {
            $this->call(DemoSeeder::class);
        }
    }
}
