<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Livewire\Volt\Volt;

test('registration screen can be rendered', function () {
    config(['registration.invite_only' => true]);

    $response = $this->get('/register');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.register')
        ->assertSee('Invite required');
});

test('new users can register with a sponsor invite', function () {
    config(['registration.invite_only' => true]);

    Role::create([
        'name' => 'Member',
        'slug' => 'member',
        'status' => 'active',
        'color' => 'slate',
        'permissions' => ['portal.dashboard.view', 'invites.manage', 'media.view'],
        'is_system' => true,
    ]);

    $sponsor = User::factory()->create();
    $invite = \App\Models\RegistrationInvite::query()->create([
        'sponsor_id' => $sponsor->id,
        'code' => 'TEST-CODE',
        'expires_at' => now()->addDay(),
    ]);

    $component = Volt::test('pages.auth.register')
        ->set('inviteCode', $invite->code)
        ->call('verifyInvite')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password');

    $component->call('register');

    $component->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    $user = User::where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->sponsor_id)->toBe($sponsor->id);
    expect($user->roles()->pluck('slug')->all())->toBe(['member']);
});
