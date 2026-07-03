<?php

use App\Models\Role;
use App\Support\Crm\CrmPermissions;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Role::query()
            ->where('slug', 'member')
            ->update([
                'permissions' => CrmPermissions::defaultsByRole()['member'],
                'description' => 'Registered portal member with resources, sponsor tools, and field CRM access.',
            ]);
    }

    public function down(): void
    {
        Role::query()
            ->where('slug', 'member')
            ->update([
                'permissions' => [
                    'portal.dashboard.view',
                    'invites.manage',
                    'sponsors.view-tree',
                    'media.view',
                ],
                'description' => 'Registered portal member with resources and profile access.',
            ]);
    }
};
