<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Seed the default access roles.
     */
    public function run(): void
    {
        $allPermissions = [
            'admin.dashboard.view',
            'media.view',
            'media.create',
            'media.update',
            'media.delete',
            'media.export',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'pages.manage',
            'blog.manage',
            'faqs.manage',
            'testimonials.manage',
            'leads.manage',
            'appointments.manage',
            'messages.manage',
            'settings.manage',
        ];

        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'Full system access across users, roles, media, content, operations, and settings.',
                'status' => 'active',
                'color' => 'teal',
                'permissions' => $allPermissions,
                'is_system' => true,
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Administrative access for daily management without protected system ownership.',
                'status' => 'active',
                'color' => 'cyan',
                'permissions' => array_values(array_diff($allPermissions, ['users.delete', 'settings.manage'])),
                'is_system' => true,
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Operational access for media, leads, appointments, messages, and content review.',
                'status' => 'active',
                'color' => 'emerald',
                'permissions' => [
                    'admin.dashboard.view',
                    'media.view',
                    'media.create',
                    'media.update',
                    'media.export',
                    'pages.manage',
                    'blog.manage',
                    'faqs.manage',
                    'testimonials.manage',
                    'leads.manage',
                    'appointments.manage',
                    'messages.manage',
                ],
                'is_system' => true,
            ],
            [
                'name' => 'Editor',
                'slug' => 'editor',
                'description' => 'Content and media publishing access without user or system settings control.',
                'status' => 'active',
                'color' => 'amber',
                'permissions' => [
                    'admin.dashboard.view',
                    'media.view',
                    'media.create',
                    'media.update',
                    'pages.manage',
                    'blog.manage',
                    'faqs.manage',
                    'testimonials.manage',
                ],
                'is_system' => true,
            ],
            [
                'name' => 'Member',
                'slug' => 'member',
                'description' => 'Default registered user role for read-only member portal access.',
                'status' => 'active',
                'color' => 'slate',
                'permissions' => [],
                'is_system' => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }
}
