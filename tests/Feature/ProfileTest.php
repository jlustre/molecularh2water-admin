<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

test('profile page is displayed', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'member')->first());

    $this->actingAs($user);

    $response = $this->get('/profile');

    $html = $response
        ->assertOk()
        ->assertSee('Associate Portal')
        ->assertSee('layoutSidebar()')
        ->assertSee('Workspace')
        ->assertSee('Profile Center')
        ->assertSee('Account Overview')
        ->assertSee('Email Status')
        ->assertSee('Business Lines')
        ->assertSee('Security Notes')
        ->assertDontSee('Danger Zone')
        ->assertSeeVolt('profile.update-profile-information-form')
        ->assertSeeVolt('profile.update-password-form')
        ->assertSee('Saving profile...')
        ->assertSee('Updating password...')
        ->getContent();

    expect($html)
        ->toContain('data-portal-profile-form-scope')
        ->toContain('data-portal-page-loading-overlay')
        ->toContain('portalPageLoadingOverlay');

    expect(preg_match_all('/data-portal-page-loading-overlay(?=[\s>])/i', $html))->toBe(2);
});

test('delete account panel is displayed for super admins', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'super-admin')->first());

    $this->actingAs($user);

    $this->get('/profile')
        ->assertOk()
        ->assertSee('Danger Zone')
        ->assertSeeVolt('profile.delete-user-form');
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('profile.update-profile-information-form')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $component
        ->assertHasNoErrors()
        ->assertNoRedirect();

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('profile avatar can be uploaded and replaced', function () {
    Storage::fake('public');

    $oldAvatarPath = UploadedFile::fake()
        ->image('old-avatar.jpg', 120, 120)
        ->store('avatars', 'public');
    $user = User::factory()->create(['avatar_path' => $oldAvatarPath]);

    $this->actingAs($user);

    $component = Volt::test('profile.update-profile-information-form')
        ->set('name', 'Avatar User')
        ->set('email', $user->email)
        ->set('avatar', UploadedFile::fake()->image('new-avatar.png', 240, 240))
        ->call('updateProfileInformation');

    $component
        ->assertHasNoErrors()
        ->assertNoRedirect();

    $user->refresh();

    expect($user->avatar_path)->not->toBeNull();
    expect($user->avatar_path)->toStartWith('avatars/');

    Storage::disk('public')->assertExists($user->avatar_path);
    Storage::disk('public')->assertMissing($oldAvatarPath);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('profile.update-profile-information-form')
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $component
        ->assertHasNoErrors()
        ->assertNoRedirect();

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('super admin can soft delete their account', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'super-admin')->first());

    $this->actingAs($user);

    $component = Volt::test('profile.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser');

    $component
        ->assertHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertSoftDeleted('users', ['id' => $user->id]);
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'super-admin')->first());

    $this->actingAs($user);

    $component = Volt::test('profile.delete-user-form')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $component
        ->assertHasErrors('password')
        ->assertNoRedirect();

    $this->assertNotNull($user->fresh());
});
