<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

it('allows an admin to manage users', function () {
    $admin = superAdminUser(['name' => 'Admin User']);
    $existingUser = User::factory()->create([
        'name' => 'Taylor Verified',
        'email' => 'taylor@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('User Directory')
        ->assertSee('Add User')
        ->assertSee('Taylor Verified');

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['search' => 'taylor', 'status' => 'verified']))
        ->assertOk()
        ->assertSee('Taylor Verified')
        ->assertSee('Active');

    User::factory()->inactive()->create([
        'name' => 'Inactive Member',
        'email' => 'inactive.member@example.com',
        'sponsor_id' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['account_status' => 'inactive']))
        ->assertOk()
        ->assertSee('Inactive Member')
        ->assertDontSee('Taylor Verified');

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['account_status' => 'active']))
        ->assertOk()
        ->assertSee('Taylor Verified')
        ->assertDontSee('Inactive Member');

    $this->actingAs($admin)
        ->get(route('admin.users.create'))
        ->assertOk()
        ->assertSee('Add user')
        ->assertSee('Email Status');

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Morgan Member',
            'email' => 'morgan@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'email_status' => 'verified',
            'sponsor_id' => $admin->id,
        ])
        ->assertRedirect(route('admin.users.index'));

    $createdUser = User::where('email', 'morgan@example.com')->first();

    expect($createdUser)->not->toBeNull();
    expect($createdUser->email_verified_at)->not->toBeNull();
    expect(Hash::check('password', $createdUser->password))->toBeTrue();

    $this->actingAs($admin)
        ->get(route('admin.users.edit', $createdUser))
        ->assertOk()
        ->assertSee('Edit user')
        ->assertSee('morgan@example.com');

    $this->actingAs($admin)
        ->get(route('admin.users.edit', $createdUser))
        ->assertOk()
        ->assertSee('Account Status')
        ->assertSee('Inactive users cannot sign in');

    $this->actingAs($admin)
        ->put(route('admin.users.update', $createdUser), [
            'name' => 'Morgan Updated',
            'email' => 'morgan.updated@example.com',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
            'email_status' => 'unverified',
            'account_status' => 'inactive',
            'sponsor_id' => $admin->id,
        ])
        ->assertRedirect(route('admin.users.index'));

    $createdUser->refresh();

    expect($createdUser->name)->toBe('Morgan Updated');
    expect($createdUser->email)->toBe('morgan.updated@example.com');
    expect($createdUser->email_verified_at)->toBeNull();
    expect($createdUser->is_active)->toBeFalse();
    expect(Hash::check('new-password', $createdUser->password))->toBeTrue();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $existingUser))
        ->assertRedirect(route('admin.users.index'));

    $this->assertSoftDeleted('users', [
        'id' => $existingUser->id,
    ]);
});

it('allows an admin to update a user avatar', function () {
    Storage::fake('public');

    $admin = superAdminUser();
    $oldAvatarPath = UploadedFile::fake()
        ->image('old-avatar.jpg', 120, 120)
        ->store('avatars', 'public');
    $user = User::factory()->create([
        'name' => 'Avatar Member',
        'email' => 'avatar.member@example.com',
        'sponsor_id' => $admin->id,
        'avatar_path' => $oldAvatarPath,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.edit', $user))
        ->assertOk()
        ->assertSee('Upload or replace avatar')
        ->assertSee(basename($oldAvatarPath), false);

    $this->actingAs($admin)
        ->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'email_status' => 'verified',
            'account_status' => 'active',
            'sponsor_id' => $admin->id,
            'avatar' => UploadedFile::fake()->image('new-avatar.png', 240, 240),
        ])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->avatar_path)->not->toBeNull();
    expect($user->avatar_path)->toStartWith('avatars/');
    expect($user->avatar_path)->not->toBe($oldAvatarPath);
    expect($user->avatarUrl())->toContain('/avatars/'.basename($user->avatar_path));
    expect($user->avatarUrl())->toContain('?v=');

    Storage::disk('public')->assertExists($user->avatar_path);
    Storage::disk('public')->assertMissing($oldAvatarPath);
});

it('prevents an admin from deleting their own account', function () {
    $admin = superAdminUser();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $admin))
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('status', 'You cannot delete your own account.');

    $this->assertDatabaseHas('users', [
        'id' => $admin->id,
    ]);
});

it('exports current users into the existing users seeder data file', function () {
    $admin = superAdminUser(['name' => 'Admin Exporter', 'email' => 'admin.exporter@example.com']);
    $member = User::factory()->create([
        'name' => 'Seeded Member',
        'email' => 'seeded.member@example.com',
        'sponsor_id' => $admin->id,
        'business_lines' => ['h2s'],
        'email_verified_at' => now(),
    ]);
    $member->roles()->attach(
        Role::query()->where('slug', 'member')->value('id')
    );

    $passwordHash = $member->getRawOriginal('password');

    File::shouldReceive('ensureDirectoryExists')
        ->once()
        ->with(database_path('seeders/data'));

    File::shouldReceive('put')
        ->once()
        ->withArgs(function (string $path, string $contents) use ($admin, $member, $passwordHash) {
            expect($path)->toBe(database_path('seeders/data/existing_users.php'));

            return str_contains($contents, 'return ')
                && str_contains($contents, "'email' => '{$admin->email}'")
                && str_contains($contents, "'email' => '{$member->email}'")
                && str_contains($contents, "'name' => 'Seeded Member'")
                && str_contains($contents, "'password' => '{$passwordHash}'")
                && str_contains($contents, "'sponsor_email' => '{$admin->email}'")
                && str_contains($contents, "'super-admin'")
                && str_contains($contents, "'member'")
                && ! str_contains($contents, 'password_confirmation');
        })
        ->andReturn(1);

    $this->actingAs($admin)
        ->post(route('admin.users.update-seeder'))
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('status', 'Users seeder updated with 2 users.');
});

it('denies users without users.export permission from updating the users seeder', function () {
    $this->seed(\Database\Seeders\RolesSeeder::class);

    $editor = User::factory()->create();
    $editor->roles()->attach(
        Role::query()->where('slug', 'editor')->value('id')
    );

    $this->actingAs($editor)
        ->post(route('admin.users.update-seeder'))
        ->assertForbidden();
});
