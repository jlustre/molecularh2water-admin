<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Joey Lustre',
            'email' => 'jclustre@gmail.com',
            'password' => bcrypt('Password123'),
        ]);

         $this->call([
            MediaItemsSeeder::class,
            // RolePermissionSeeder::class,
            // SuperAdminSeeder::class,
        ]);
    }
}
