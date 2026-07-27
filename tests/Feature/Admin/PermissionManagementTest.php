<?php

use App\Models\Permission;
use App\Models\PermissionCategory;
use App\Models\Role;
use Database\Seeders\PermissionCatalogSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\File;

it('lists permissions with search category and role filters', function () {
    $this->seed(RolesSeeder::class);

    $admin = superAdminUser();
    $editor = Role::query()->where('slug', 'editor')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('admin.permissions.index'))
        ->assertOk()
        ->assertSee('Permissions')
        ->assertSee('Capability Catalog')
        ->assertSee('media.view')
        ->assertSee('Media Library (Content)');

    $this->actingAs($admin)
        ->get(route('admin.permissions.index', [
            'search' => 'media.view',
            'category' => 'media',
            'role' => $editor->id,
            'assignment' => 'assigned',
            'per_page' => 10,
        ]))
        ->assertOk()
        ->assertSee('media.view')
        ->assertSee('View media library')
        ->assertDontSee('roles.manage');
});

it('includes permissions for newer system and workspace features', function () {
    $admin = superAdminUser();

    $this->actingAs($admin)
        ->get(route('admin.permissions.index', [
            'category' => 'tasks',
            'per_page' => 50,
        ]))
        ->assertOk()
        ->assertSee('tasks.assign')
        ->assertSee('Tasks Management: assign to any portal member (System)')
        ->assertSee('tasks.view')
        ->assertSee('View My Tasks (Workspace)');

    $this->actingAs($admin)
        ->get(route('admin.permissions.index', [
            'category' => 'sales',
            'per_page' => 50,
        ]))
        ->assertOk()
        ->assertSee('Sales & Catalog (System)')
        ->assertSee('sales.view')
        ->assertSee('products.view');

    $this->actingAs($admin)
        ->get(route('admin.permissions.index', [
            'category' => 'fulfillment',
            'per_page' => 50,
        ]))
        ->assertOk()
        ->assertSee('Orders & Fulfillment')
        ->assertSee('fulfillment.view')
        ->assertSee('fulfillment.manage');

    $this->actingAs($admin)
        ->get(route('admin.permissions.index', [
            'category' => 'installers',
            'per_page' => 50,
        ]))
        ->assertOk()
        ->assertSee('installers.view')
        ->assertSee('installers.manage');

    $this->actingAs($admin)
        ->get(route('admin.permissions.index', [
            'category' => 'customer_directory',
            'per_page' => 50,
        ]))
        ->assertOk()
        ->assertSee('customer-directory.view')
        ->assertSee('customer-directory.manage');

    $this->actingAs($admin)
        ->get(route('admin.permissions.index', [
            'category' => 'permissions',
            'per_page' => 50,
        ]))
        ->assertOk()
        ->assertSee('permissions.view')
        ->assertSee('permissions.manage');

    $this->actingAs($admin)
        ->get(route('admin.permissions.index', [
            'category' => 'reports',
            'per_page' => 50,
        ]))
        ->assertOk()
        ->assertSee('CRM Insights')
        ->assertSee('crm.dashboard.view')
        ->assertSee('reports.view');

    $this->actingAs($admin)
        ->get(route('admin.permissions.index', [
            'category' => 'portal',
            'per_page' => 50,
        ]))
        ->assertOk()
        ->assertSee('portal.dashboard.view')
        ->assertSee('invites.manage')
        ->assertSee('sponsors.view-tree');
});

it('allows an admin to assign roles to a permission', function () {
    $this->seed(RolesSeeder::class);

    $admin = superAdminUser();
    $editor = Role::query()->where('slug', 'editor')->firstOrFail();
    $member = Role::query()->where('slug', 'member')->firstOrFail();

    expect($editor->permissions)->toContain('media.view');
    expect($member->permissions)->toContain('media.view');

    $this->actingAs($admin)
        ->get(route('admin.permissions.edit', 'media.view'))
        ->assertOk()
        ->assertSee('Assign roles')
        ->assertSee('media.view')
        ->assertSee($editor->name)
        ->assertSee($member->name);

    $this->actingAs($admin)
        ->put(route('admin.permissions.update', 'media.view'), [
            'role_ids' => [$editor->id],
        ])
        ->assertRedirect(route('admin.permissions.index'))
        ->assertSessionHas('status');

    $editor->refresh();
    $member->refresh();

    expect($editor->permissions)->toContain('media.view');
    expect($member->permissions)->not->toContain('media.view');
});

it('paginates the permissions catalog', function () {
    $admin = superAdminUser();

    $this->actingAs($admin)
        ->get(route('admin.permissions.index', ['per_page' => 5]))
        ->assertOk()
        ->assertSee('5 / page');
});

it('shows the update seeder action for super admins', function () {
    $this->seed(RolesSeeder::class);
    $admin = superAdminUser();

    $this->actingAs($admin)
        ->get(route('admin.permissions.index'))
        ->assertOk()
        ->assertSee('Update Seeder');
});

it('updates the roles seeder from the permissions page', function () {
    $this->seed(RolesSeeder::class);
    $admin = superAdminUser(['name' => 'Admin User']);

    $role = Role::create([
        'name' => 'Fulfillment Lead',
        'slug' => 'fulfillment-lead',
        'description' => 'Owns fulfillment queues.',
        'status' => 'active',
        'color' => 'cyan',
        'permissions' => ['fulfillment.view', 'fulfillment.manage'],
        'is_system' => false,
    ]);

    File::shouldReceive('put')
        ->once()
        ->withArgs(function (string $path, string $contents) use ($role) {
            expect($path)->toBe(database_path('seeders/RolesSeeder.php'));

            return str_contains($contents, 'class RolesSeeder')
                && str_contains($contents, "Role::query()->updateOrCreate")
                && str_contains($contents, "'slug' => 'fulfillment-lead'")
                && str_contains($contents, "'fulfillment.view'")
                && str_contains($contents, "'fulfillment.manage'")
                && str_contains($contents, (string) $role->slug);
        })
        ->andReturn(1);

    $this->actingAs($admin)
        ->post(route('admin.permissions.update-seeder'))
        ->assertRedirect(route('admin.permissions.index'))
        ->assertSessionHas('status', fn (string $status) => str_contains($status, 'RolesSeeder.php updated'));
});

it('seeds the built-in permission catalog into the database', function () {
    $this->seed(PermissionCatalogSeeder::class);

    expect(PermissionCategory::query()->where('key', 'tasks')->exists())->toBeTrue()
        ->and(Permission::query()->where('key', 'tasks.assign')->where('is_system', true)->exists())->toBeTrue();
});

it('allows creating a custom category and permission', function () {
    $this->seed(RolesSeeder::class);
    $admin = superAdminUser();

    $this->actingAs($admin)
        ->post(route('admin.permission-catalog.categories.store'), [
            'key' => 'custom_tools',
            'label' => 'Custom Tools',
        ])
        ->assertRedirect(route('admin.permissions.index'))
        ->assertSessionHas('status');

    $category = PermissionCategory::query()->where('key', 'custom_tools')->firstOrFail();
    expect($category->is_system)->toBeFalse();

    $this->actingAs($admin)
        ->post(route('admin.permission-catalog.permissions.store'), [
            'permission_category_id' => $category->id,
            'key' => 'custom.tools.view',
            'label' => 'View custom tools',
        ])
        ->assertRedirect(route('admin.permissions.index'))
        ->assertSessionHas('status');

    expect(Permission::query()->where('key', 'custom.tools.view')->exists())->toBeTrue();

    $this->actingAs($admin)
        ->get(route('admin.permissions.index', [
            'search' => 'custom.tools.view',
            'per_page' => 50,
        ]))
        ->assertOk()
        ->assertSee('custom.tools.view')
        ->assertSee('View custom tools')
        ->assertSee('Custom Tools');

    $this->actingAs($admin)
        ->get(route('admin.roles.create'))
        ->assertOk()
        ->assertSee('Custom Tools')
        ->assertSee('custom.tools.view');
});

it('assigns a custom permission to a role', function () {
    $this->seed(RolesSeeder::class);
    $admin = superAdminUser();
    $editor = Role::query()->where('slug', 'editor')->firstOrFail();

    $category = PermissionCategory::query()->create([
        'key' => 'ops',
        'label' => 'Operations',
        'sort_order' => 99,
        'is_system' => false,
    ]);

    Permission::query()->create([
        'permission_category_id' => $category->id,
        'key' => 'ops.review',
        'label' => 'Review operations',
        'sort_order' => 0,
        'is_system' => false,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.permissions.update', 'ops.review'), [
            'role_ids' => [$editor->id],
        ])
        ->assertRedirect(route('admin.permissions.index'));

    expect($editor->refresh()->permissions)->toContain('ops.review');
});

it('blocks deleting system permissions and strips custom ones from roles', function () {
    $this->seed(RolesSeeder::class);
    $admin = superAdminUser();

    $system = Permission::query()->where('key', 'media.view')->firstOrFail();

    $this->actingAs($admin)
        ->delete(route('admin.permission-catalog.permissions.destroy', $system))
        ->assertRedirect(route('admin.permissions.index'))
        ->assertSessionHas('status', 'System permissions cannot be deleted.');

    expect(Permission::query()->where('key', 'media.view')->exists())->toBeTrue();

    $category = PermissionCategory::query()->create([
        'key' => 'temp',
        'label' => 'Temp',
        'sort_order' => 100,
        'is_system' => false,
    ]);

    $custom = Permission::query()->create([
        'permission_category_id' => $category->id,
        'key' => 'temp.cleanup',
        'label' => 'Temp cleanup',
        'sort_order' => 0,
        'is_system' => false,
    ]);

    $editor = Role::query()->where('slug', 'editor')->firstOrFail();
    $editor->update([
        'permissions' => array_values(array_unique(array_merge($editor->permissions ?? [], ['temp.cleanup']))),
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.permission-catalog.permissions.destroy', $custom))
        ->assertRedirect(route('admin.permissions.index'))
        ->assertSessionHas('status', 'Permission deleted.');

    expect(Permission::query()->where('key', 'temp.cleanup')->exists())->toBeFalse()
        ->and($editor->refresh()->permissions)->not->toContain('temp.cleanup');
});
