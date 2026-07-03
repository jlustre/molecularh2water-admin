<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Support\Crm\CrmPermissions;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = CrmPermissions::defaultsByRole();

        $roles = [
            [
                'name' => 'Joey Lustre',
                'slug' => 'super-admin',
                'description' => 'Complete access to the website, admin area, CRM, users, and system settings.',
                'status' => 'active',
                'color' => 'teal',
                'permissions' => $defaults['super-admin'],
                'is_system' => true,
            ],
            [
                'name' => 'Team Admin',
                'slug' => 'team-admin',
                'description' => 'Leads a team: manages managers and consultants, team-scoped CRM oversight, and admin tools.',
                'status' => 'active',
                'color' => 'cyan',
                'permissions' => $defaults['team-admin'],
                'is_system' => true,
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Organization administrator with full CRM visibility and daily operations.',
                'status' => 'active',
                'color' => 'blue',
                'permissions' => $defaults['admin'],
                'is_system' => true,
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Team manager with pipeline, reports, and visibility across assigned team members.',
                'status' => 'active',
                'color' => 'emerald',
                'permissions' => $defaults['manager'],
                'is_system' => true,
            ],
            [
                'name' => 'Consultant',
                'slug' => 'consultant',
                'description' => 'Field associate with portal CRM access to assigned leads, tasks, and calendar.',
                'status' => 'active',
                'color' => 'indigo',
                'permissions' => $defaults['consultant'],
                'is_system' => true,
            ],
            [
                'name' => 'Editor',
                'slug' => 'editor',
                'description' => 'Content and media publishing without CRM access.',
                'status' => 'active',
                'color' => 'amber',
                'permissions' => $defaults['editor'],
                'is_system' => true,
            ],
            [
                'name' => 'Member',
                'slug' => 'member',
                'description' => 'Registered portal member with resources, sponsor tools, and field CRM access.',
                'status' => 'active',
                'color' => 'slate',
                'permissions' => $defaults['member'],
                'is_system' => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                ['slug' => $role['slug']],
                $role
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
