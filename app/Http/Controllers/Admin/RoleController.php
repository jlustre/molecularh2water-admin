<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    private const STATUSES = [
        'active' => 'Active',
        'draft' => 'Draft',
        'archived' => 'Archived',
    ];

    private const COLORS = [
        'teal' => 'Teal',
        'emerald' => 'Emerald',
        'cyan' => 'Cyan',
        'amber' => 'Amber',
        'rose' => 'Rose',
        'slate' => 'Slate',
    ];

    private const PERMISSIONS = [
        'dashboard' => [
            'label' => 'Dashboard',
            'items' => [
                'admin.dashboard.view' => 'View admin dashboard',
            ],
        ],
        'media' => [
            'label' => 'Media Library',
            'items' => [
                'media.view' => 'View media',
                'media.create' => 'Create media',
                'media.update' => 'Update media',
                'media.delete' => 'Delete media',
                'media.export' => 'Update media seeder',
            ],
        ],
        'users' => [
            'label' => 'Users',
            'items' => [
                'users.view' => 'View users',
                'users.create' => 'Create users',
                'users.update' => 'Update users',
                'users.delete' => 'Delete users',
            ],
        ],
        'content' => [
            'label' => 'Content',
            'items' => [
                'pages.manage' => 'Manage pages',
                'blog.manage' => 'Manage blog',
                'faqs.manage' => 'Manage FAQs',
                'testimonials.manage' => 'Manage testimonials',
            ],
        ],
        'operations' => [
            'label' => 'Operations',
            'items' => [
                'leads.manage' => 'Manage leads',
                'appointments.manage' => 'Manage appointments',
                'messages.manage' => 'Manage contact messages',
                'settings.manage' => 'Manage settings',
            ],
        ],
    ];

    public function index(Request $request): View
    {
        $query = Role::query()
            ->withCount('users')
            ->latest();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && array_key_exists($request->status, self::STATUSES)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('permission')) {
            $permission = $request->string('permission')->toString();

            if (in_array($permission, $this->permissionKeys(), true)) {
                $query->whereJsonContains('permissions', $permission);
            }
        }

        return view('admin.roles.index', [
            'roles' => $query->paginate((int) $request->integer('per_page', 10))->withQueryString(),
            'statuses' => self::STATUSES,
            'permissions' => self::PERMISSIONS,
            'totalRoles' => Role::query()->count(),
            'activeRoles' => Role::query()->where('status', 'active')->count(),
            'assignedRoles' => Role::query()->has('users')->count(),
            'permissionCount' => count($this->permissionKeys()),
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.create', $this->formData(new Role(['status' => 'active', 'color' => 'teal'])));
    }

    public function store(Request $request): RedirectResponse
    {
        $attributes = $this->validatedAttributes($request);
        $attributes['slug'] = Str::slug($attributes['slug'] ?: $attributes['name']);
        $attributes['permissions'] = $this->validPermissions($request->input('permissions', []));
        $attributes['is_system'] = $request->boolean('is_system');

        $role = Role::create($attributes);
        $role->users()->sync($request->input('user_ids', []));

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Role created.');
    }

    public function edit(Role $role): View
    {
        $role->load('users');

        return view('admin.roles.edit', $this->formData($role));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $attributes = $this->validatedAttributes($request, $role);
        $attributes['slug'] = Str::slug($attributes['slug'] ?: $attributes['name']);
        $attributes['permissions'] = $this->validPermissions($request->input('permissions', []));
        $attributes['is_system'] = $request->boolean('is_system');

        $role->update($attributes);
        $role->users()->sync($request->input('user_ids', []));

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            return redirect()
                ->route('admin.roles.index')
                ->with('status', 'System roles cannot be deleted.');
        }

        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Role deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Role $role): array
    {
        return [
            'role' => $role,
            'statuses' => self::STATUSES,
            'colors' => self::COLORS,
            'permissions' => self::PERMISSIONS,
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedAttributes(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('roles', 'slug')->ignore($role)],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(self::STATUSES))],
            'color' => ['required', 'string', 'in:'.implode(',', array_keys(self::COLORS))],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($this->permissionKeys())],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function permissionKeys(): array
    {
        return collect(self::PERMISSIONS)
            ->flatMap(fn (array $group) => array_keys($group['items']))
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $permissions
     * @return array<int, string>
     */
    private function validPermissions(array $permissions): array
    {
        return collect($permissions)
            ->intersect($this->permissionKeys())
            ->values()
            ->all();
    }
}
