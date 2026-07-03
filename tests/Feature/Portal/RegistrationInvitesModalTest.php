<?php

use App\Livewire\Portal\RegistrationInvitesModal;
use App\Models\RegistrationInvite;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

function inviteModalSponsor(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

it('shows member invites quick action on the dashboard', function () {
    $sponsor = inviteModalSponsor();

    $this->actingAs($sponsor)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Member Invites')
        ->assertSeeLivewire(RegistrationInvitesModal::class);
});

it('opens the modal and generates invites with the same functionality as the page', function () {
    $sponsor = inviteModalSponsor();

    Livewire::actingAs($sponsor)
        ->test(RegistrationInvitesModal::class)
        ->call('open')
        ->assertSet('show', true)
        ->assertSee('Generate invite')
        ->assertSee('Your invites')
        ->set('label', 'Modal prospect')
        ->call('generateInvite')
        ->assertHasNoErrors()
        ->assertSee('Email invite')
        ->assertSee('Copy link');

    expect(RegistrationInvite::query()->where('sponsor_id', $sponsor->id)->count())->toBe(1);
});

it('closes the modal and resets state', function () {
    $sponsor = inviteModalSponsor();

    Livewire::actingAs($sponsor)
        ->test(RegistrationInvitesModal::class)
        ->call('open')
        ->set('label', 'Temporary')
        ->call('close')
        ->assertSet('show', false)
        ->assertSet('label', '');
});
