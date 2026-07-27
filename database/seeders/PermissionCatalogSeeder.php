<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\PermissionCategory;
use App\Support\Crm\CrmPermissions;
use Illuminate\Database\Seeder;

class PermissionCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $sort = 0;

        foreach (CrmPermissions::groups() as $categoryKey => $group) {
            $category = PermissionCategory::query()->updateOrCreate(
                ['key' => $categoryKey],
                [
                    'label' => $group['label'],
                    'sort_order' => $sort++,
                    'is_system' => true,
                ],
            );

            $itemSort = 0;

            foreach ($group['items'] as $permissionKey => $label) {
                Permission::query()->updateOrCreate(
                    ['key' => $permissionKey],
                    [
                        'permission_category_id' => $category->id,
                        'label' => $label,
                        'sort_order' => $itemSort++,
                        'is_system' => true,
                    ],
                );
            }
        }
    }
}
