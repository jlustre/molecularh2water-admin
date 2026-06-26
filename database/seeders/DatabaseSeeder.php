<?php

namespace Database\Seeders;

use App\Models\Role;
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

        $this->call([
            RolesSeeder::class,
            MediaItemsSeeder::class,
            // RolePermissionSeeder::class,
            // SuperAdminSeeder::class,
        ]);

        $user = User::query()->updateOrCreate(
            ['email' => 'jclustre@gmail.com'],
            [
                'name' => 'Joey Lustre',
                'password' => bcrypt('Password123'),
            ],
        );

        if ($superAdminRole = Role::query()->where('slug', 'super-admin')->first()) {
            $user->roles()->syncWithoutDetaching([$superAdminRole->id]);
        }
    }
}
