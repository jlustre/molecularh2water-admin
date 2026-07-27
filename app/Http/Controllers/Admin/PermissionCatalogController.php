<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\PermissionCategory;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PermissionCatalogController extends Controller
{
    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/', 'unique:permission_categories,key'],
            'label' => ['required', 'string', 'max:255'],
        ]);

        $maxSort = (int) PermissionCategory::query()->max('sort_order');

        PermissionCategory::query()->create([
            'key' => $validated['key'],
            'label' => $validated['label'],
            'sort_order' => $maxSort + 1,
            'is_system' => false,
        ]);

        return redirect()
            ->route('admin.permissions.index')
            ->with('status', 'Category “'.$validated['label'].'” created.');
    }

    public function editCategory(PermissionCategory $category): View
    {
        return view('admin.permissions.edit-category', [
            'category' => $category,
        ]);
    }

    public function updateCategory(Request $request, PermissionCategory $category): RedirectResponse
    {
        $rules = [
            'label' => ['required', 'string', 'max:255'],
        ];

        if (! $category->is_system) {
            $rules['key'] = [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('permission_categories', 'key')->ignore($category->id),
            ];
        }

        $validated = $request->validate($rules);

        $category->update([
            'label' => $validated['label'],
            ...($category->is_system ? [] : ['key' => $validated['key']]),
        ]);

        return redirect()
            ->route('admin.permissions.index')
            ->with('status', 'Category updated.');
    }

    public function destroyCategory(PermissionCategory $category): RedirectResponse
    {
        if ($category->is_system) {
            return redirect()
                ->route('admin.permissions.index')
                ->with('status', 'System categories cannot be deleted.');
        }

        $keys = $category->permissions()->pluck('key')->all();
        $this->stripPermissionKeysFromRoles($keys);
        $category->delete();

        return redirect()
            ->route('admin.permissions.index')
            ->with('status', 'Category deleted.');
    }

    public function storePermission(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'permission_category_id' => ['required', 'integer', 'exists:permission_categories,id'],
            'key' => ['required', 'string', 'max:150', 'regex:/^[a-z][a-z0-9_.-]*$/', 'unique:permissions,key'],
            'label' => ['required', 'string', 'max:255'],
        ]);

        $maxSort = (int) Permission::query()
            ->where('permission_category_id', $validated['permission_category_id'])
            ->max('sort_order');

        Permission::query()->create([
            'permission_category_id' => $validated['permission_category_id'],
            'key' => $validated['key'],
            'label' => $validated['label'],
            'sort_order' => $maxSort + 1,
            'is_system' => false,
        ]);

        return redirect()
            ->route('admin.permissions.index')
            ->with('status', 'Permission “'.$validated['key'].'” created.');
    }

    public function editPermission(Permission $permission): View
    {
        return view('admin.permissions.edit-permission', [
            'permission' => $permission->load('category'),
            'categories' => PermissionCategory::query()
                ->orderBy('sort_order')
                ->orderBy('key')
                ->get(['id', 'key', 'label']),
        ]);
    }

    public function updatePermission(Request $request, Permission $permission): RedirectResponse
    {
        $rules = [
            'label' => ['required', 'string', 'max:255'],
            'permission_category_id' => ['required', 'integer', 'exists:permission_categories,id'],
        ];

        if (! $permission->is_system) {
            $rules['key'] = [
                'required',
                'string',
                'max:150',
                'regex:/^[a-z][a-z0-9_.-]*$/',
                Rule::unique('permissions', 'key')->ignore($permission->id),
            ];
        }

        $validated = $request->validate($rules);
        $previousKey = $permission->key;

        $permission->update([
            'label' => $validated['label'],
            'permission_category_id' => $validated['permission_category_id'],
            ...($permission->is_system ? [] : ['key' => $validated['key']]),
        ]);

        if (! $permission->is_system && $previousKey !== $permission->key) {
            $this->renamePermissionKeyOnRoles($previousKey, $permission->key);
        }

        return redirect()
            ->route('admin.permissions.index')
            ->with('status', 'Permission updated.');
    }

    public function destroyPermission(Permission $permission): RedirectResponse
    {
        if ($permission->is_system) {
            return redirect()
                ->route('admin.permissions.index')
                ->with('status', 'System permissions cannot be deleted.');
        }

        $this->stripPermissionKeysFromRoles([$permission->key]);
        $permission->delete();

        return redirect()
            ->route('admin.permissions.index')
            ->with('status', 'Permission deleted.');
    }

    /**
     * @param  list<string>  $keys
     */
    private function stripPermissionKeysFromRoles(array $keys): void
    {
        if ($keys === []) {
            return;
        }

        Role::query()
            ->orderBy('id')
            ->each(function (Role $role) use ($keys) {
                $current = array_values($role->permissions ?? []);
                $next = array_values(array_filter(
                    $current,
                    fn (string $key) => ! in_array($key, $keys, true),
                ));

                if ($next !== $current) {
                    $role->update(['permissions' => $next]);
                }
            });
    }

    private function renamePermissionKeyOnRoles(string $from, string $to): void
    {
        Role::query()
            ->orderBy('id')
            ->each(function (Role $role) use ($from, $to) {
                $current = array_values($role->permissions ?? []);

                if (! in_array($from, $current, true)) {
                    return;
                }

                $next = array_values(array_unique(array_map(
                    fn (string $key) => $key === $from ? $to : $key,
                    $current,
                )));

                $role->update(['permissions' => $next]);
            });
    }
}
