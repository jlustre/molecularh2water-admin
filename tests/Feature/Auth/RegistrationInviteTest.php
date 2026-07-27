<?php

use App\Livewire\Portal\RegistrationInvites;
use App\Models\RegistrationInvite;
use App\Models\Role;
use App\Models\User;
use App\Services\RegistrationInviteService;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;
use Livewire\Volt\Volt;

beforeEach(function () {
    config(['registration.invite_only' => true]);
    $this->seed(RolesSeeder::class);
});

function sponsorUser(string $name = 'Sponsor User'): User
{
    $user = User::factory()->create(['name' => $name]);
    $consultant = Role::query()->where('slug', 'consultant')->first();
    $consultant->update([
        'permissions' => array_values(array_unique(array_merge(
            $consultant->permissions ?? [],
            ['invites.manage'],
        ))),
    ]);
    $user->roles()->attach($consultant);

    return $user;
}

it('shows invite required message on register without a code', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Invite required')
        ->assertSee('Verify code')
        ->assertDontSee('wire:submit="register"', false);
});

it('opens registration form from a valid invite link', function () {
    $sponsor = sponsorUser('Pat Sponsor');
    $invite = app(RegistrationInviteService::class)->generate($sponsor, $sponsor);

    $this->get(route('register.invite', ['code' => $invite->code]))
        ->assertOk()
        ->assertSee('Pat Sponsor')
        ->assertSee('Create your account')
        ->assertSee('If this is not your sponsor, do not register with this link. Ask your correct sponsor for their personal invite link first.');
});

it('does not show the wrong-sponsor notice without a sponsor invite', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertDontSee('If this is not your sponsor, do not register with this link. Ask your correct sponsor for their personal invite link first.');
});

it('registers a member with a one-time invite and links sponsor', function () {
    $sponsor = sponsorUser();
    $invite = app(RegistrationInviteService::class)->generate($sponsor, $sponsor);

    $component = Volt::test('pages.auth.register')
        ->set('inviteCode', $invite->code)
        ->call('verifyInvite')
        ->set('name', 'Invited Member')
        ->set('email', 'invited@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password');

    $component->call('register');

    $component->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'invited@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->sponsor_id)->toBe($sponsor->id)
        ->and($user->roles()->where('slug', 'member')->exists())->toBeTrue()
        ->and($user->hasPermission('leads.view'))->toBeTrue()
        ->and($user->hasPermission('crm.dashboard.view'))->toBeFalse()
        ->and($user->hasPermission('invites.manage'))->toBeFalse();

    $invite->refresh();

    expect($invite->isConsumed())->toBeTrue()
        ->and($invite->registered_user_id)->toBe($user->id);
});

it('rejects reusing a consumed invite code', function () {
    $sponsor = sponsorUser();
    $invite = app(RegistrationInviteService::class)->generate($sponsor, $sponsor);

    Volt::test('pages.auth.register')
        ->set('inviteCode', $invite->code)
        ->call('verifyInvite')
        ->set('name', 'First Member')
        ->set('email', 'first@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertRedirect(route('dashboard', absolute: false));

    Volt::test('pages.auth.register')
        ->set('inviteCode', $invite->code)
        ->set('inviteAccepted', true)
        ->set('name', 'Second Member')
        ->set('email', 'second@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasErrors(['inviteCode']);
});

it('allows sponsors to generate invites in the portal', function () {
    $sponsor = sponsorUser();

    Livewire::actingAs($sponsor)
        ->test(RegistrationInvites::class)
        ->set('sponsorUserId', $sponsor->id)
        ->call('generateInvite')
        ->assertHasNoErrors()
        ->assertSet('generatedCode', fn ($code) => is_string($code) && $code !== '')
        ->assertSee('Email invite')
        ->assertSee('Copy link');

    expect(RegistrationInvite::query()->where('sponsor_id', $sponsor->id)->count())->toBe(1);
});

it('allows sponsors to email an available invite', function () {
    \Illuminate\Support\Facades\Mail::fake();

    $sponsor = sponsorUser('Alex Sponsor');
    $invite = app(RegistrationInviteService::class)->generate($sponsor, $sponsor);

    Livewire::actingAs($sponsor)
        ->test(RegistrationInvites::class)
        ->call('openEmailModal', $invite->id)
        ->assertSet('showEmailModal', true)
        ->set('recipientEmail', 'prospect@example.com')
        ->set('emailMessage', 'Join our team!')
        ->call('sendInviteEmail')
        ->assertHasNoErrors()
        ->assertSet('showEmailModal', false);

    \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\RegistrationInviteMail::class, function ($mail) use ($sponsor, $invite) {
        return $mail->hasTo('prospect@example.com')
            && $mail->invite->is($invite)
            && $mail->sponsor->is($sponsor)
            && $mail->personalMessage === 'Join our team!';
    });
});

it('rejects emailing a consumed invite', function () {
    $sponsor = sponsorUser();
    $invite = app(RegistrationInviteService::class)->generate($sponsor, $sponsor);
    $invite->update(['consumed_at' => now()]);

    Livewire::actingAs($sponsor)
        ->test(RegistrationInvites::class)
        ->call('openEmailModal', $invite->id)
        ->assertSet('showEmailModal', false);
});

it('cannot email another sponsors invite', function () {
    $sponsor = sponsorUser();
    $other = sponsorUser('Other Sponsor');
    $invite = app(RegistrationInviteService::class)->generate($other, $other);

    expect(fn () => Livewire::actingAs($sponsor)
        ->test(RegistrationInvites::class)
        ->call('openEmailModal', $invite->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('denies invite management without permission', function () {
    $editor = User::factory()->create();
    $editor->roles()->attach(Role::query()->where('slug', 'editor')->first());

    $this->actingAs($editor)
        ->get(route('portal.invites'))
        ->assertForbidden();
});

it('allows open registration when invite only is disabled', function () {
    config(['registration.invite_only' => false]);

    Volt::test('pages.auth.register')
        ->set('name', 'Open Member')
        ->set('email', 'open@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertRedirect(route('dashboard', absolute: false));

    expect(User::query()->where('email', 'open@example.com')->exists())->toBeTrue();
});
