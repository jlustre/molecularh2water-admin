<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

function unverifiedMember(): User
{
    $user = User::factory()->unverified()->create();
    $user->roles()->attach(Role::query()->where('slug', 'member')->first());

    return $user;
}

it('shows email verification banner on dashboard for unverified users', function () {
    $user = unverifiedMember();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Email verification required')
        ->assertSee('Resend verification email')
        ->assertSeeLivewire('email-verification-banner');
});

it('shows email verification banner on profile for unverified users', function () {
    $user = unverifiedMember();

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('Email verification required')
        ->assertSee('Resend verification email')
        ->assertSeeLivewire('email-verification-banner');
});

it('does not show email verification banner for verified users', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'member')->first());

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Email verification required');

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertDontSee('Email verification required');
});

it('can resend verification email from the banner', function () {
    Notification::fake();

    $user = unverifiedMember();

    Livewire::actingAs($user)
        ->test('email-verification-banner')
        ->call('sendVerification')
        ->assertSet('linkSent', true)
        ->assertSee('A new verification link has been sent to your email address.');

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('leaves newly registered invite users unverified on dashboard', function () {
    config(['registration.invite_only' => true]);

    $sponsor = User::factory()->create();
    $sponsor->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $invite = app(\App\Services\RegistrationInviteService::class)->generate($sponsor);

    Volt::test('pages.auth.register')
        ->set('inviteCode', $invite->code)
        ->call('verifyInvite')
        ->set('name', 'Invited Member')
        ->set('email', 'invited@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'invited@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasVerifiedEmail())->toBeFalse();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Email verification required');
});
