<?php

namespace App\Services\Admin;

use App\Models\Role;
use Illuminate\Support\Facades\File;

class RolesSeederExporter
{
    /**
     * Export current roles (including permission assignments) into RolesSeeder.php.
     *
     * @return int Number of roles written
     */
    public function export(): int
    {
        $roles = Role::query()
            ->orderBy('id')
            ->get([
                'name',
                'slug',
                'description',
                'status',
                'color',
                'permissions',
                'is_system',
            ])
            ->map(fn (Role $role) => [
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'status' => $role->status,
                'color' => $role->color,
                'permissions' => array_values($role->permissions ?? []),
                'is_system' => (bool) $role->is_system,
            ])
            ->all();

        $exportedRoles = var_export($roles, true);
        $generatedAt = now()->toDateTimeString();
        $path = database_path('seeders/RolesSeeder.php');

        File::put($path, <<<PHP
<?php

namespace Database\\Seeders;

use App\\Models\\Role;
use Illuminate\\Database\\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Seed roles from the admin export generated at {$generatedAt}.
     */
    public function run(): void
    {
        \$roles = {$exportedRoles};

        foreach (\$roles as \$role) {
            Role::query()->updateOrCreate(
                ['slug' => \$role['slug']],
                \$role
            );
        }

        Role::query()
            ->where('slug', 'agent')
            ->update([
                'slug' => 'consultant-legacy',
                'name' => 'Agent (Legacy)',
                'status' => 'inactive',
            ]);
    }
}
PHP);

        return count($roles);
    }
}
