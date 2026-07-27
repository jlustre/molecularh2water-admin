<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\PermissionCategory;
use App\Models\Role;
use App\Services\Admin\RolesSeederExporter;
use App\Support\Crm\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(Request $request): View
    {
        $roles = Role::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'color', 'permissions', 'status']);

        $categoryModels = PermissionCatalog::tablesReady()
            ? PermissionCategory::query()
                ->orderBy('sort_order')
                ->orderBy('key')
                ->get(['id', 'key', 'label', 'is_system'])
            : collect();

        $categories = collect(PermissionCatalog::groups())
            ->mapWithKeys(fn (array $group, string $key) => [$key => $group['label']])
            ->all();

        $catalog = $this->filteredCatalog($request, $roles);
        $perPage = max(5, min(100, (int) $request->integer('per_page', 15)));
        $page = max(1, (int) $request->integer('page', 1));
        $total = $catalog->count();
        $items = $catalog->forPage($page, $perPage)->values();

        $permissions = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $fullCatalog = $this->catalogWithRoles($roles);

        return view('admin.permissions.index', [
            'permissions' => $permissions,
            'categories' => $categories,
            'categoryModels' => $categoryModels,
            'roles' => $roles,
            'totalPermissions' => count(PermissionCatalog::all()),
            'assignedPermissions' => $fullCatalog
                ->filter(fn (array $row) => $row['roles']->isNotEmpty())
                ->count(),
            'unassignedPermissions' => $fullCatalog
                ->filter(fn (array $row) => $row['roles']->isEmpty())
                ->count(),
            'categoryCount' => count($categories),
        ]);
    }

    public function edit(string $permission): View
    {
        $row = $this->permissionRow($permission);
        abort_unless($row !== null, 404);

        $roles = Role::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'color', 'permissions', 'status']);

        return view('admin.permissions.edit', [
            'permission' => $row,
            'roles' => $roles,
            'selectedRoleIds' => $roles
                ->filter(fn (Role $role) => in_array($permission, $role->permissions ?? [], true))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all(),
        ]);
    }

    public function update(Request $request, string $permission): RedirectResponse
    {
        abort_unless(PermissionCatalog::has($permission), 404);

        $validated = $request->validate([
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', Rule::exists('roles', 'id')],
        ]);

        $selectedRoleIds = collect($validated['role_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        Role::query()
            ->orderBy('id')
            ->each(function (Role $role) use ($permission, $selectedRoleIds) {
                $permissions = collect($role->permissions ?? [])->values();
                $shouldHave = in_array($role->id, $selectedRoleIds, true);
                $hasPermission = $permissions->containsStrict($permission);

                if ($shouldHave && ! $hasPermission) {
                    $permissions->push($permission);
                }

                if (! $shouldHave && $hasPermission) {
                    $permissions = $permissions->reject(fn (string $key) => $key === $permission)->values();
                }

                $next = $permissions->values()->all();
                $current = array_values($role->permissions ?? []);

                if ($next !== $current) {
                    $role->update([
                        'permissions' => $next,
                    ]);
                }
            });

        return redirect()
            ->route('admin.permissions.index')
            ->with('status', 'Permission roles updated for '.$permission.'.');
    }

    /**
     * Export current role permission assignments into RolesSeeder.
     */
    public function updateSeeder(RolesSeederExporter $exporter): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $count = $exporter->export();

        return redirect()
            ->route('admin.permissions.index')
            ->with('status', 'RolesSeeder.php updated with '.$count.' role'.($count === 1 ? '' : 's').'.');
    }

    /**
     * @param  Collection<int, Role>  $roles
     * @return Collection<int, array{id: int|null, key: string, label: string, category: string, category_label: string, category_id: int|null, is_system: bool, roles: Collection<int, Role>}>
     */
    private function catalogWithRoles(Collection $roles): Collection
    {
        if (PermissionCatalog::tablesReady() && Permission::query()->exists()) {
            return Permission::query()
                ->select('permissions.*')
                ->join('permission_categories', 'permission_categories.id', '=', 'permissions.permission_category_id')
                ->with('category:id,key,label,sort_order')
                ->orderBy('permission_categories.sort_order')
                ->orderBy('permissions.sort_order')
                ->orderBy('permissions.key')
                ->get()
                ->map(function (Permission $permission) use ($roles) {
                    return [
                        'id' => $permission->id,
                        'key' => $permission->key,
                        'label' => $permission->label,
                        'category' => $permission->category?->key ?? 'uncategorized',
                        'category_label' => $permission->category?->label ?? 'Uncategorized',
                        'category_id' => $permission->permission_category_id,
                        'is_system' => (bool) $permission->is_system,
                        'roles' => $roles
                            ->filter(fn (Role $role) => in_array($permission->key, $role->permissions ?? [], true))
                            ->values(),
                    ];
                })
                ->values();
        }

        return collect(PermissionCatalog::groups())
            ->flatMap(function (array $group, string $category) use ($roles) {
                return collect($group['items'])->map(function (string $label, string $key) use ($group, $category, $roles) {
                    return [
                        'id' => null,
                        'key' => $key,
                        'label' => $label,
                        'category' => $category,
                        'category_label' => $group['label'],
                        'category_id' => null,
                        'is_system' => true,
                        'roles' => $roles
                            ->filter(fn (Role $role) => in_array($key, $role->permissions ?? [], true))
                            ->values(),
                    ];
                });
            })
            ->values();
    }

    /**
     * @param  Collection<int, Role>  $roles
     * @return Collection<int, array{id: int|null, key: string, label: string, category: string, category_label: string, category_id: int|null, is_system: bool, roles: Collection<int, Role>}>
     */
    private function filteredCatalog(Request $request, Collection $roles): Collection
    {
        $catalog = $this->catalogWithRoles($roles);

        if ($request->filled('search')) {
            $search = mb_strtolower($request->string('search')->toString());

            $catalog = $catalog->filter(function (array $row) use ($search) {
                return str_contains(mb_strtolower($row['key']), $search)
                    || str_contains(mb_strtolower($row['label']), $search)
                    || str_contains(mb_strtolower($row['category_label']), $search);
            });
        }

        if ($request->filled('category') && array_key_exists($request->string('category')->toString(), PermissionCatalog::groups())) {
            $category = $request->string('category')->toString();
            $catalog = $catalog->filter(fn (array $row) => $row['category'] === $category);
        }

        if ($request->filled('role')) {
            $roleId = (int) $request->integer('role');
            $catalog = $catalog->filter(
                fn (array $row) => $row['roles']->contains(fn (Role $role) => $role->id === $roleId)
            );
        }

        if ($request->filled('assignment')) {
            $assignment = $request->string('assignment')->toString();

            $catalog = match ($assignment) {
                'assigned' => $catalog->filter(fn (array $row) => $row['roles']->isNotEmpty()),
                'unassigned' => $catalog->filter(fn (array $row) => $row['roles']->isEmpty()),
                default => $catalog,
            };
        }

        return $catalog->values();
    }

    /**
     * @return array{key: string, label: string, category: string, category_label: string}|null
     */
    private function permissionRow(string $permission): ?array
    {
        foreach (PermissionCatalog::groups() as $category => $group) {
            if (! array_key_exists($permission, $group['items'])) {
                continue;
            }

            return [
                'key' => $permission,
                'label' => $group['items'][$permission],
                'category' => $category,
                'category_label' => $group['label'],
            ];
        }

        return null;
    }
}
