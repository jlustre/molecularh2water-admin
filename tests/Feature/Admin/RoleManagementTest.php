<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesSeeder;

it('allows an admin to manage roles and assigned users', function () {
    $admin = User::factory()->create();
    $assignedUser = User::factory()->create([
        'name' => 'Taylor Member',
        'email' => 'taylor.member@example.com',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.roles.index'))
        ->assertOk()
        ->assertSee('Roles &amp; Permissions', false)
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
    $admin = User::factory()->create();
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

    foreach (['super-admin', 'admin', 'manager', 'editor', 'member'] as $slug) {
        $this->assertDatabaseHas('roles', [
            'slug' => $slug,
            'status' => 'active',
            'is_system' => true,
        ]);
    }

    expect(Role::where('slug', 'super-admin')->first()->permissions)
        ->toContain('users.delete', 'settings.manage');

    expect(Role::where('slug', 'member')->first()->permissions)->toBe([]);
});
