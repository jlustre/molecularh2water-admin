<?php

use App\Enums\Crm\LeadLifecycle;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Referral;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

it('enrolls client in after-sales when moved to closed won', function () {
    $agent = User::factory()->create();
    $agent->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $wonStage = FunnelStage::query()->where('slug', 'closed-won')->first();

    $lead = \App\Models\Crm\Lead::factory()->assignedTo($agent)->prospect()->create([
        'funnel_id' => $wonStage->funnel_id,
        'funnel_stage_id' => FunnelStage::query()->where('slug', 'ready-to-purchase')->where('funnel_id', $wonStage->funnel_id)->value('id'),
    ]);

    app(\App\Services\Crm\FunnelService::class)->moveLead($lead, $wonStage, $agent);

    $lead = $lead->fresh(['stage', 'funnel']);

    expect($lead->lifecycle)->toBe(LeadLifecycle::Client)
        ->and($lead->funnel?->slug)->toBe('after-sales-funnel')
        ->and($lead->stage?->slug)->toBe('warranty-registration');
});

it('logs a referral and creates a referred lead', function () {
    $agent = User::factory()->create();
    $agent->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $client = \App\Models\Crm\Lead::factory()->assignedTo($agent)->create([
        'lifecycle' => LeadLifecycle::Client,
    ]);

    Livewire::actingAs($agent)
        ->test(\App\Livewire\Crm\LeadReferralsPanel::class, ['lead' => $client])
        ->call('toggleForm')
        ->set('first_name', 'Jamie')
        ->set('last_name', 'Referral')
        ->set('email', 'jamie@example.com')
        ->call('saveReferral')
        ->assertHasNoErrors();

    $referral = Referral::query()->where('referrer_lead_id', $client->id)->first();

    expect($referral)->not->toBeNull()
        ->and($referral->referred->lifecycle)->toBe(LeadLifecycle::Lead)
        ->and($referral->referred->referred_by_lead_id)->toBe($client->id)
        ->and($referral->referred->stage?->slug)->toBe('referral-received');
});

it('marks referral converted when referred lead closes won', function () {
    $agent = User::factory()->create();
    $agent->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $client = \App\Models\Crm\Lead::factory()->assignedTo($agent)->create([
        'lifecycle' => LeadLifecycle::Client,
    ]);

    $referral = app(\App\Services\Crm\ReferralService::class)->recordReferral($client, [
        'first_name' => 'Pat',
        'last_name' => 'Prospect',
        'email' => 'pat@example.com',
    ], $agent);

    $wonStage = FunnelStage::query()->where('slug', 'closed-won')->first();
    $referred = $referral->referred;
    $referred->update([
        'funnel_id' => $wonStage->funnel_id,
        'funnel_stage_id' => FunnelStage::query()->where('slug', 'decision-pending')->where('funnel_id', $wonStage->funnel_id)->value('id'),
    ]);

    app(\App\Services\Crm\FunnelService::class)->moveLead($referred, $wonStage, $agent);

    expect($referral->fresh()->status->value)->toBe('converted');
});

it('issues referral rewards via service', function () {
    $agent = User::factory()->create();
    $agent->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $client = \App\Models\Crm\Lead::factory()->assignedTo($agent)->create([
        'lifecycle' => LeadLifecycle::Client,
    ]);

    $referral = app(\App\Services\Crm\ReferralService::class)->recordReferral($client, [
        'first_name' => 'Reward',
        'last_name' => 'Test',
    ], $agent);

    $referral->update(['status' => 'converted']);

    app(\App\Services\Crm\ReferralService::class)->markRewarded($referral, [
        'reward_type' => 'gift_card',
        'reward_amount' => 50,
        'reward_notes' => 'Thank you gift card',
    ], $agent);

    expect($referral->fresh()->status->value)->toBe('rewarded')
        ->and((float) $referral->fresh()->reward_amount)->toBe(50.0);
});

it('shows referral reward form on client profile', function () {
    $agent = User::factory()->create();
    $agent->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $client = \App\Models\Crm\Lead::factory()->assignedTo($agent)->create([
        'lifecycle' => LeadLifecycle::Client,
    ]);

    $referral = app(\App\Services\Crm\ReferralService::class)->recordReferral($client, [
        'first_name' => 'Reward',
        'last_name' => 'Test',
    ], $agent);

    $referral->update(['status' => 'converted']);

    Livewire::actingAs($agent)
        ->test(\App\Livewire\Crm\LeadReferralsPanel::class, ['lead' => $client->fresh()])
        ->call('startReward', $referral->id)
        ->assertSet('rewardingReferralId', $referral->id);
});

it('builds referral leaderboard for managers', function () {
    $manager = User::factory()->create();
    $manager->roles()->attach(Role::query()->where('slug', 'manager')->first());

    $client = \App\Models\Crm\Lead::factory()->assignedTo($manager)->create([
        'lifecycle' => LeadLifecycle::Client,
        'first_name' => 'Top',
        'last_name' => 'Referrer',
    ]);

    app(\App\Services\Crm\ReferralService::class)->recordReferral($client, [
        'first_name' => 'A',
        'last_name' => 'One',
    ], $manager);

    app(\App\Services\Crm\ReferralService::class)->recordReferral($client, [
        'first_name' => 'B',
        'last_name' => 'Two',
    ], $manager);

    $board = app(\App\Services\Crm\ReferralService::class)->leaderboard($manager);

    expect($board)->toHaveCount(1)
        ->and($board->first()->referrals)->toBe(2);
});
