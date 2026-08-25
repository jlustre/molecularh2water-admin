<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\File;

it('allows an admin to manage roles and assigned users', function () {
    $admin = superAdminUser();
    $assignedUser = User::factory()->create([
        'name' => 'Taylor Member',
        'email' => 'taylor.member@example.com',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.roles.index'))
        ->assertOk()
        ->assertSee('Create and manage access roles')
        ->assertSee('Add Role');

    $this->actingAs($admin)
        ->get(route('admin.roles.create'))
        ->assertOk()
        ->assertSee('Add role')
        ->assertSee('Media Library')
        ->assertSee('Assigned Users');

    $this->actingAs($admin)
        ->post(route('admin.roles.store'), [
            'name' => 'Media Manager',
            'slug' => 'media-manager',
            'description' => 'Can manage media library content.',
            'status' => 'active',
            'color' => 'teal',
            'permissions' => ['media.view', 'media.create', 'media.update'],
            'user_ids' => [$assignedUser->id],
        ])
        ->assertRedirect(route('admin.roles.index'));

    $role = Role::where('slug', 'media-manager')->first();

    expect($role)->not->toBeNull();
    expect($role->permissions)->toBe(['media.view', 'media.create', 'media.update']);
    expect($role->users()->whereKey($assignedUser)->exists())->toBeTrue();

    $this->actingAs($admin)
        ->get(route('admin.roles.index', ['search' => 'media', 'permission' => 'media.create']))
        ->assertOk()
        ->assertSee('Media Manager');

    $this->actingAs($admin)
        ->get(route('admin.roles.edit', $role))
        ->assertOk()
        ->assertSee('Edit role')
        ->assertSee('media-manager');

    $this->actingAs($admin)
        ->put(route('admin.roles.update', $role), [
            'name' => 'Media Director',
            'slug' => 'media-director',
            'description' => 'Expanded media role.',
            'status' => 'draft',
            'color' => 'cyan',
            'permissions' => ['media.view', 'media.export'],
            'user_ids' => [$admin->id],
        ])
        ->assertRedirect(route('admin.roles.index'));

    $role->refresh();

    expect($role->name)->toBe('Media Director');
    expect($role->slug)->toBe('media-director');
    expect($role->status)->toBe('draft');
    expect($role->permissions)->toBe(['media.view', 'media.export']);
    expect($role->users()->whereKey($assignedUser)->exists())->toBeFalse();
    expect($role->users()->whereKey($admin)->exists())->toBeTrue();

    $this->actingAs($admin)
        ->delete(route('admin.roles.destroy', $role))
        ->assertRedirect(route('admin.roles.index'));

    $this->assertDatabaseMissing('roles', [
        'id' => $role->id,
    ]);
});

it('prevents deleting system roles', function () {
    $admin = superAdminUser();
    $role = Role::create([
        'name' => 'System Admin',
        'slug' => 'system-admin',
        'status' => 'active',
        'color' => 'teal',
        'permissions' => ['admin.dashboard.view'],
        'is_system' => true,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.roles.destroy', $role))
        ->assertRedirect(route('admin.roles.index'))
        ->assertSessionHas('status', 'System roles cannot be deleted.');

    $this->assertDatabaseHas('roles', [
        'id' => $role->id,
    ]);
});

it('seeds the default access roles', function () {
    $this->seed(RolesSeeder::class);

    foreach (['super-admin', 'team-admin', 'admin', 'manager', 'consultant', 'editor', 'member'] as $slug) {
        $this->assertDatabaseHas('roles', [
            'slug' => $slug,
            'status' => 'active',
            'is_system' => true,
        ]);
    }

    expect(Role::where('slug', 'super-admin')->first()->permissions)
        ->toContain(
            'users.delete',
            'settings.manage',
            'roles.manage',
            'roles.export',
            'permissions.view',
            'permissions.manage',
            'warranty.view',
            'installation-questionnaires.view',
            'issue-reports.view',
            'issue-reports.manage',
        );

    expect(Role::where('slug', 'admin')->first()->permissions)
        ->toContain('roles.manage', 'permissions.manage', 'warranty.manage', 'installation-questionnaires.manage')
        ->not->toContain('users.delete', 'settings.manage', 'issue-reports.view', 'issue-reports.manage');

    expect(Role::where('slug', 'member')->first()->permissions)
        ->toContain('portal.dashboard.view', 'media.view', 'leads.view')
        ->not->toContain('roles.manage', 'warranty.view', 'invites.manage', 'crm.dashboard.view', 'reports.view');
});

it('lists every permission group on the role form', function () {
    $admin = superAdminUser();

    $this->actingAs($admin)
        ->get(route('admin.roles.create'))
        ->assertOk()
        ->assertSee('Warranty Registrations')
        ->assertSee('Installation Questionnaires')
        ->assertSee('Issue Reports')
        ->assertSee('Installer Management')
        ->assertSee('Customers Management')
        ->assertSee('Email Mappings')
        ->assertSee('Sales & Catalog (System)')
        ->assertSee('Orders & Fulfillment')
        ->assertSee('CRM Insights')
        ->assertSee('>Roles</p>', false)
        ->assertSee('>Permissions</p>', false)
        ->assertSee('Website Forms')
        ->assertSee('warranty.view')
        ->assertSee('roles.manage')
        ->assertSee('roles.export')
        ->assertSee('permissions.view')
        ->assertSee('permissions.manage')
        ->assertSee('website-forms.manage')
        ->assertSee('installation-questionnaires.manage')
        ->assertSee('issue-reports.view')
        ->assertSee('issue-reports.manage')
        ->assertSee('installers.view')
        ->assertSee('installers.manage')
        ->assertSee('customer-directory.view')
        ->assertSee('customer-directory.manage')
        ->assertSee('email-mappings.manage')
        ->assertSee('tasks.assign')
        ->assertSee('fulfillment.view')
        ->assertSee('fulfillment.manage')
        ->assertSee('sales.view')
        ->assertSee('products.manage')
        ->assertSee('crm.dashboard.view')
        ->assertSee('invites.manage');
});

it('shows the update seeder action for super admins', function () {
    $admin = superAdminUser();

    $this->actingAs($admin)
        ->get(route('admin.roles.index'))
        ->assertOk()
        ->assertSee('Update Seeder');
});

it('updates the roles seeder from current records', function () {
    $admin = superAdminUser(['name' => 'Admin User']);

    $role = Role::create([
        'name' => 'Content Lead',
        'slug' => 'content-lead',
        'description' => 'Owns media and FAQs.',
        'status' => 'active',
        'color' => 'amber',
        'permissions' => ['media.view', 'faqs.manage'],
        'is_system' => false,
    ]);

    File::shouldReceive('put')
        ->once()
        ->withArgs(function (string $path, string $contents) use ($role) {
            expect($path)->toBe(database_path('seeders/RolesSeeder.php'));

            return str_contains($contents, 'class RolesSeeder')
                && str_contains($contents, "Role::query()->updateOrCreate")
                && str_contains($contents, "'slug' => 'content-lead'")
                && str_contains($contents, "'name' => 'Content Lead'")
                && str_contains($contents, "'media.view'")
                && str_contains($contents, "'faqs.manage'")
                && str_contains($contents, (string) $role->slug);
        })
        ->andReturn(1);

    $this->actingAs($admin)
        ->post(route('admin.roles.update-seeder'))
        ->assertRedirect(route('admin.roles.index'))
        ->assertSessionHas('status', fn (string $status) => str_contains($status, 'RolesSeeder.php updated'));
});
