<?php

use App\Models\Role;
use App\Support\Crm\CrmPermissions;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $manager = Role::query()->where('slug', 'manager')->first();

        if (! $manager) {
            return;
        }

        $manager->update([
            'permissions' => CrmPermissions::defaultsByRole()['manager'],
        ]);
    }

    public function down(): void
    {
        $manager = Role::query()->where('slug', 'manager')->first();

        if (! $manager) {
            return;
        }

        $permissions = $manager->permissions ?? [];

        if (! in_array('admin.dashboard.view', $permissions, true)) {
            $permissions[] = 'admin.dashboard.view';
        }

        $manager->update(['permissions' => array_values($permissions)]);
    }
};
