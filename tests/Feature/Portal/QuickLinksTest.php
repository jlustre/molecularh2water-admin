<?php

use App\Livewire\Portal\AppointmentsModal;
use App\Livewire\Portal\DemosModal;
use App\Livewire\Portal\PhoneCallsModal;
use App\Livewire\Portal\ProspectsModal;
use App\Livewire\Portal\QuickLinks;
use App\Livewire\Portal\ReferralsModal;
use App\Livewire\Portal\RegistrationInvitesModal;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

function quickLinksConsultant(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

it('renders quick link actions without a full page form', function () {
    $consultant = quickLinksConsultant();

    Livewire::actingAs($consultant)
        ->test(QuickLinks::class)
        ->assertSee('Quick Links')
        ->assertSee('Prospects')
        ->assertSee('Demos')
        ->assertSee('Phone Calls')
        ->assertSee('Appointments')
        ->assertSee('Referrals/Leads')
        ->assertSeeLivewire(AppointmentsModal::class)
        ->assertSeeLivewire(ProspectsModal::class)
        ->assertSeeLivewire(DemosModal::class)
        ->assertSeeLivewire(PhoneCallsModal::class)
        ->assertSeeLivewire(ReferralsModal::class);
});

it('opens the demos modal from the quick links component', function () {
    $consultant = quickLinksConsultant();

    Livewire::actingAs($consultant)
        ->test(QuickLinks::class)
        ->call('openDemos')
        ->assertDispatched('open-demos');
});

it('opens the referrals modal from the quick links component', function () {
    $consultant = quickLinksConsultant();

    Livewire::actingAs($consultant)
        ->test(QuickLinks::class)
        ->call('openReferrals')
        ->assertDispatched('open-referrals');
});

it('opens member invites modal for members with invite permission', function () {
    $member = User::factory()->create();
    $memberRole = Role::query()->where('slug', 'member')->firstOrFail();
    $memberRole->update([
        'permissions' => array_values(array_unique(array_merge(
            $memberRole->permissions ?? [],
            ['invites.manage'],
        ))),
    ]);
    $member->roles()->attach($memberRole);

    Livewire::actingAs($member)
        ->test(QuickLinks::class)
        ->assertSee('Member Invites')
        ->assertSeeLivewire(RegistrationInvitesModal::class)
        ->call('openMemberInvites')
        ->assertDispatched('open-member-invites');
});
