<?php

namespace App\Support\Crm;

use App\Models\Permission;
use App\Models\PermissionCategory;
use Illuminate\Support\Facades\Schema;

class PermissionCatalog
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::flat());
    }

    /**
     * @return array<string, string>
     */
    public static function flat(): array
    {
        $flat = [];

        foreach (self::groups() as $group) {
            foreach ($group['items'] as $key => $label) {
                $flat[$key] = $label;
            }
        }

        return $flat;
    }

    /**
     * @return array<string, array{label: string, items: array<string, string>}>
     */
    public static function groups(): array
    {
        if (! self::tablesReady() || PermissionCategory::query()->doesntExist()) {
            return CrmPermissions::groups();
        }

        return PermissionCategory::query()
            ->with(['permissions' => fn ($query) => $query->orderBy('sort_order')->orderBy('key')])
            ->orderBy('sort_order')
            ->orderBy('key')
            ->get()
            ->mapWithKeys(function (PermissionCategory $category) {
                return [
                    $category->key => [
                        'label' => $category->label,
                        'items' => $category->permissions
                            ->mapWithKeys(fn (Permission $permission) => [$permission->key => $permission->label])
                            ->all(),
                    ],
                ];
            })
            ->all();
    }

    public static function has(string $permission): bool
    {
        return in_array($permission, self::all(), true);
    }

    public static function tablesReady(): bool
    {
        return Schema::hasTable('permission_categories')
            && Schema::hasTable('permissions');
    }
}
