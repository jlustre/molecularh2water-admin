<?php

use App\Enums\Crm\LeadLifecycle;
use App\Enums\Crm\LeadStatus;
use App\Livewire\Crm\FunnelBoard;
use App\Livewire\Crm\FunnelManager;
use App\Models\Crm\Funnel;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Lead;
use App\Models\Crm\LostReason;
use App\Models\Crm\Prospect;
use App\Models\Crm\TimelineEvent;
use App\Models\Role;
use App\Models\User;
use App\Support\Crm\PipelineContacts;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

function funnelAdmin(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'admin')->first());

    return $user;
}

function funnelAgent(string $name = 'Pipeline Agent'): User
{
    $user = User::factory()->create(['name' => $name]);
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

function salesFunnelId(): int
{
    return (int) Funnel::query()->where('slug', 'sales-funnel')->value('id');
}

it('moves a lead between early stages and logs a timeline event', function () {
    $agent = funnelAgent();
    $firstStage = FunnelStage::query()->where('funnel_id', salesFunnelId())->orderBy('sort_order')->first();
    $secondStage = FunnelStage::query()->where('funnel_id', salesFunnelId())->orderBy('sort_order')->skip(1)->first();

    $lead = Lead::factory()->assignedTo($agent)->create([
        'funnel_id' => $firstStage->funnel_id,
        'funnel_stage_id' => $firstStage->id,
    ]);

    Livewire::actingAs($agent)
        ->test(FunnelBoard::class)
        ->set('funnelId', salesFunnelId())
        ->call('moveLead', 'lead', $lead->id, $secondStage->id)
        ->assertHasNoErrors();

    $lead->refresh();

    expect($lead->funnel_stage_id)->toBe($secondStage->id)
        ->and($lead->lifecycleSlug())->toBe(LeadLifecycle::Lead);
    expect(TimelineEvent::query()
        ->where('contact_type', 'lead')
        ->where('contact_id', $lead->id)
        ->where('event_type', 'funnel_moved')
        ->exists())->toBeTrue();
});

it('converts a lead to prospect when moved to Demo Invitation Sent', function () {
    $agent = funnelAgent();
    $newLead = FunnelStage::query()->where('funnel_id', salesFunnelId())->where('slug', 'new-lead')->first();
    $demoInvite = FunnelStage::query()->where('funnel_id', salesFunnelId())->where('slug', 'demo-invitation-sent')->first();

    $lead = Lead::factory()->assignedTo($agent)->create([
        'funnel_id' => $newLead->funnel_id,
        'funnel_stage_id' => $newLead->id,
        'email' => 'demo.invite@example.com',
    ]);

    Livewire::actingAs($agent)
        ->test(FunnelBoard::class)
        ->set('funnelId', salesFunnelId())
        ->call('moveLead', 'lead', $lead->id, $demoInvite->id)
        ->assertHasNoErrors();

    expect(Lead::query()->where('email', 'demo.invite@example.com')->exists())->toBeFalse();

    $prospect = Prospect::query()->where('email', 'demo.invite@example.com')->first();

    expect($prospect)->not->toBeNull()
        ->and($prospect->lifecycleSlug())->toBe(LeadLifecycle::Prospect)
        ->and($prospect->stage?->slug)->toBe('demo-invitation-sent');
});

it('converts a lead to prospect when moved to Qualified as Prospect', function () {
    $agent = funnelAgent();
    $newLead = FunnelStage::query()->where('funnel_id', salesFunnelId())->where('slug', 'new-lead')->first();
    $qualified = FunnelStage::query()->where('funnel_id', salesFunnelId())->where('slug', 'qualified')->first();

    $lead = Lead::factory()->assignedTo($agent)->create([
        'funnel_id' => $newLead->funnel_id,
        'funnel_stage_id' => $newLead->id,
        'email' => 'qualify.me@example.com',
    ]);

    Livewire::actingAs($agent)
        ->test(FunnelBoard::class)
        ->set('funnelId', salesFunnelId())
        ->call('moveLead', 'lead', $lead->id, $qualified->id)
        ->assertHasNoErrors();

    expect(Lead::query()->where('email', 'qualify.me@example.com')->exists())->toBeFalse();

    $prospect = Prospect::query()->where('email', 'qualify.me@example.com')->first();

    expect($prospect)->not->toBeNull()
        ->and($prospect->lifecycleSlug())->toBe(LeadLifecycle::Prospect)
        ->and($prospect->stage?->slug)->toBe('qualified');
});

it('converts a prospect back to lead when moved before Qualified', function () {
    $agent = funnelAgent();
    $contacted = FunnelStage::query()->where('funnel_id', salesFunnelId())->where('slug', 'contacted')->first();
    $qualified = FunnelStage::query()->where('funnel_id', salesFunnelId())->where('slug', 'qualified')->first();

    $prospect = Prospect::factory()->assignedTo($agent)->create([
        'funnel_id' => $qualified->funnel_id,
        'funnel_stage_id' => $qualified->id,
        'email' => 'demote.me@example.com',
    ]);

    Livewire::actingAs($agent)
        ->test(FunnelBoard::class)
        ->set('funnelId', salesFunnelId())
        ->call('moveLead', 'prospect', $prospect->id, $contacted->id)
        ->assertHasNoErrors();

    expect(Prospect::query()->where('email', 'demote.me@example.com')->exists())->toBeFalse();

    $lead = Lead::query()->where('email', 'demote.me@example.com')->first();

    expect($lead)->not->toBeNull()
        ->and($lead->lifecycleSlug())->toBe(LeadLifecycle::Lead)
        ->and($lead->stage?->slug)->toBe('contacted');
});

it('keeps stage cards after dismissing the calendar suggestion', function () {
    $agent = funnelAgent();
    $newLead = FunnelStage::query()->where('funnel_id', salesFunnelId())->where('slug', 'new-lead')->first();
    $qualified = FunnelStage::query()->where('funnel_id', salesFunnelId())->where('slug', 'qualified')->first();

    $existing = Prospect::factory()->assignedTo($agent)->create([
        'first_name' => 'Stay',
        'last_name' => 'Visible',
        'funnel_id' => $qualified->funnel_id,
        'funnel_stage_id' => $qualified->id,
        'email' => 'stay.visible@example.com',
    ]);

    $moving = Lead::factory()->assignedTo($agent)->create([
        'first_name' => 'Move',
        'last_name' => 'Me',
        'funnel_id' => $newLead->funnel_id,
        'funnel_stage_id' => $newLead->id,
        'email' => 'move.me@example.com',
    ]);

    $component = Livewire::actingAs($agent)
        ->test(FunnelBoard::class)
        ->set('funnelId', salesFunnelId())
        ->call('moveLead', 'lead', $moving->id, $qualified->id)
        ->assertSet('showCalendarSuggestion', true)
        ->assertSee('Stay Visible')
        ->assertSee('Move Me')
        ->call('dismissCalendarSuggestion')
        ->assertSet('showCalendarSuggestion', false)
        ->assertSee('Stay Visible')
        ->assertSee('Move Me');

    expect(Prospect::query()->where('email', 'stay.visible@example.com')->exists())->toBeTrue()
        ->and(Prospect::query()->where('email', 'move.me@example.com')->exists())->toBeTrue()
        ->and($existing->fresh()->funnel_stage_id)->toBe($qualified->id);
});

it('shows Lead badge text for contacts on early funnel stages', function () {
    $agent = funnelAgent();
    $newLead = FunnelStage::query()->where('funnel_id', salesFunnelId())->where('slug', 'new-lead')->first();

    Lead::factory()->assignedTo($agent)->create([
        'first_name' => 'Badge',
        'last_name' => 'Lead',
        'funnel_id' => $newLead->funnel_id,
        'funnel_stage_id' => $newLead->id,
    ]);

    Livewire::actingAs($agent)
        ->test(FunnelBoard::class)
        ->set('funnelId', salesFunnelId())
        ->assertSee('Badge Lead')
        ->assertSee('Lead');
});

it('requires a lost reason when moving to a lost stage', function () {
    $agent = funnelAgent();
    $openStage = FunnelStage::query()
        ->where('funnel_id', salesFunnelId())
        ->where('is_lost', false)
        ->where('is_won', false)
        ->orderBy('sort_order')
        ->first();
    $lostStage = FunnelStage::query()
        ->where('funnel_id', salesFunnelId())
        ->where('is_lost', true)
        ->first();

    $lead = Lead::factory()->assignedTo($agent)->create([
        'funnel_id' => $openStage->funnel_id,
        'funnel_stage_id' => $openStage->id,
        'status' => 'new',
    ]);

    Livewire::actingAs($agent)
        ->test(FunnelBoard::class)
        ->set('funnelId', salesFunnelId())
        ->call('requestMoveLead', 'lead', $lead->id, $lostStage->id)
        ->assertSet('showLostModal', true);

    Livewire::actingAs($agent)
        ->test(FunnelBoard::class)
        ->set('pendingContactType', 'lead')
        ->set('pendingLeadId', $lead->id)
        ->set('pendingStageId', $lostStage->id)
        ->set('lostReasonId', LostReason::query()->where('slug', 'bought-competitor-product')->value('id'))
        ->call('confirmLostMove')
        ->assertHasNoErrors();

    expect(Lead::query()->whereKey($lead->id)->exists())->toBeFalse();

    $prospect = Prospect::query()->where('email', $lead->email)->first()
        ?? Prospect::query()->where('first_name', $lead->first_name)->where('last_name', $lead->last_name)->first();

    expect($prospect)->not->toBeNull()
        ->and($prospect->funnel_stage_id)->toBe($lostStage->id)
        ->and($prospect->lifecycleSlug())->toBe(LeadLifecycle::Prospect)
        ->and($prospect->status)->toBe(LeadStatus::New)
        ->and($prospect->lost_reason_id)->toBe(LostReason::query()->where('slug', 'bought-competitor-product')->value('id'))
        ->and($prospect->lost_reason)->toBe('Bought Competitor Product');
});

it('enrolls client in after-sales when moved to closed won on the board', function () {
    $agent = funnelAgent();
    $openStage = FunnelStage::query()
        ->where('funnel_id', salesFunnelId())
        ->where('is_won', false)
        ->where('is_lost', false)
        ->orderBy('sort_order')
        ->first();
    $wonStage = FunnelStage::query()->where('slug', 'closed-won')->where('funnel_id', salesFunnelId())->first();

    $lead = Lead::factory()->assignedTo($agent)->create([
        'funnel_id' => $openStage->funnel_id,
        'funnel_stage_id' => $openStage->id,
        'status' => 'negotiating',
        'email' => 'closed.won.board@example.com',
    ]);

    Livewire::actingAs($agent)
        ->test(FunnelBoard::class)
        ->set('funnelId', salesFunnelId())
        ->call('moveLead', 'lead', $lead->id, $wonStage->id);

    $customer = \App\Models\Crm\Customer::query()->where('email', 'closed.won.board@example.com')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->lifecycle)->toBe(LeadLifecycle::Client)
        ->and($customer->status)->toBe(LeadStatus::Customer)
        ->and($customer->funnel?->slug)->toBe('after-sales-funnel')
        ->and($customer->stage?->slug)->toBe('warranty-registration');
});

it('filters the board by lifecycle', function () {
    $agent = funnelAgent();
    $salesFunnel = Funnel::query()->where('slug', 'sales-funnel')->first();
    $stage = FunnelStage::query()
        ->where('funnel_id', $salesFunnel->id)
        ->orderBy('sort_order')
        ->first();

    Lead::factory()->assignedTo($agent)->create([
        'funnel_id' => $stage->funnel_id,
        'funnel_stage_id' => $stage->id,
        'first_name' => 'Visible',
        'last_name' => 'Lead',
    ]);

    Prospect::factory()->assignedTo($agent)->create([
        'funnel_id' => $stage->funnel_id,
        'funnel_stage_id' => $stage->id,
        'first_name' => 'Hidden',
        'last_name' => 'Prospect',
    ]);

    Livewire::actingAs($agent)
        ->test(FunnelBoard::class)
        ->set('funnelId', $salesFunnel->id)
        ->set('lifecycleFilter', 'lead')
        ->assertSee('Visible Lead')
        ->assertDontSee('Hidden Prospect');
});

it('lets admins manage funnel stages', function () {
    $admin = funnelAdmin();

    $this->actingAs($admin)
        ->get(route('admin.crm.funnels.index'))
        ->assertOk()
        ->assertSee('Pipeline Stages');

    Livewire::actingAs($admin)
        ->test(FunnelManager::class)
        ->set('newStageName', 'Demo Scheduled')
        ->set('newStageColor', 'blue')
        ->call('addStage')
        ->assertHasNoErrors();

    expect(FunnelStage::query()->where('name', 'Demo Scheduled')->exists())->toBeTrue();
});

it('prevents deleting a stage that still has leads', function () {
    $admin = funnelAdmin();
    $stage = FunnelStage::query()->where('funnel_id', salesFunnelId())->orderBy('sort_order')->first();

    Lead::factory()->create([
        'funnel_id' => $stage->funnel_id,
        'funnel_stage_id' => $stage->id,
        'assigned_user_id' => $admin->id,
    ]);

    Livewire::actingAs($admin)
        ->test(FunnelManager::class)
        ->set('funnelId', salesFunnelId())
        ->call('deleteStage', $stage->id)
        ->assertHasErrors('stage');

    expect(FunnelStage::query()->whereKey($stage->id)->exists())->toBeTrue();
});

it('switches between secondary funnels on the pipeline board', function () {
    $agent = funnelAgent();
    $recruiting = Funnel::query()->where('slug', 'recruiting-funnel')->first();
    $corporate = Funnel::query()->where('slug', 'corporate-sales-funnel')->first();

    Livewire::actingAs($agent)
        ->test(FunnelBoard::class)
        ->set('funnelId', $recruiting->id)
        ->assertSee('Prospecting')
        ->set('funnelId', $corporate->id)
        ->assertSee('Inquiry');
});

it('blocks agents from moving another users lead on the pipeline', function () {
    $agentA = funnelAgent('Pipeline Agent A');
    $agentB = funnelAgent('Pipeline Agent B');

    $stageId = FunnelStage::query()->where('funnel_id', salesFunnelId())->orderBy('sort_order')->value('id');
    $targetStageId = FunnelStage::query()->where('funnel_id', salesFunnelId())->orderByDesc('sort_order')->value('id');

    $lead = Lead::factory()->assignedTo($agentB)->create([
        'funnel_id' => FunnelStage::query()->whereKey($stageId)->value('funnel_id'),
        'funnel_stage_id' => $stageId,
    ]);

    try {
        Livewire::actingAs($agentA)
            ->test(FunnelBoard::class)
            ->call('moveLead', 'lead', $lead->id, $targetStageId);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
        // Scoped lookup blocks access to another user's lead.
    }

    expect($lead->fresh()->funnel_stage_id)->toBe($stageId);
});

it('shows referral leads on the referral funnel board and builder counts', function () {
    $agent = funnelAgent();
    $referralFunnel = Funnel::query()->where('slug', 'referral-funnel')->firstOrFail();
    $entryStage = FunnelStage::query()
        ->where('funnel_id', $referralFunnel->id)
        ->where('slug', 'referral-received')
        ->firstOrFail();

    $beforeCount = PipelineContacts::countForStage($entryStage->id, $agent);

    $client = \App\Models\Crm\Customer::factory()->assignedTo($agent)->create();
    $referral = app(\App\Services\Crm\ReferralService::class)->recordReferral($client, [
        'first_name' => 'Board',
        'last_name' => 'Referral',
        'email' => 'board.referral@example.com',
    ], $agent);

    Livewire::actingAs($agent)
        ->test(FunnelBoard::class)
        ->set('funnelId', $referralFunnel->id)
        ->assertSee('Board Referral')
        ->assertSee('Referral Received');

    expect(PipelineContacts::countForStage($entryStage->id, $agent))->toBe($beforeCount + 1)
        ->and($referral->referred->funnel_stage_id)->toBe($entryStage->id);
});
