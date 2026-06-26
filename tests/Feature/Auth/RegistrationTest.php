<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Livewire\Volt\Volt;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.register');
});

test('new users can register', function () {
    Role::create([
        'name' => 'Super Admin',
        'slug' => 'super-admin',
        'status' => 'active',
        'color' => 'teal',
        'permissions' => ['admin.dashboard.view'],
        'is_system' => true,
    ]);

    Role::create([
        'name' => 'Member',
        'slug' => 'member',
        'status' => 'active',
        'color' => 'slate',
        'permissions' => [],
        'is_system' => true,
    ]);

    $component = Volt::test('pages.auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password');

    $component->call('register');

    $component->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    $user = User::where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->roles()->pluck('slug')->all())->toBe(['member']);
    expect($user->roles()->where('slug', 'super-admin')->exists())->toBeFalse();
});
