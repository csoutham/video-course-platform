<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CourseCatalogSeeder::class);

        User::factory()->create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
        ]);

        User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);
    }
}
