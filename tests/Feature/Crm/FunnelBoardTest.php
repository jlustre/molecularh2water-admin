<?php

use App\Enums\Crm\LeadLifecycle;
use App\Enums\Crm\LeadStatus;
use App\Livewire\Crm\FunnelBoard;
use App\Livewire\Crm\FunnelManager;
use App\Models\Crm\Funnel;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Lead;
use App\Models\Crm\LostReason;
use App\Models\Crm\TimelineEvent;
use App\Models\Role;
use App\Models\User;
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

it('moves a lead between stages and logs a timeline event', function () {
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
        ->call('moveLead', $lead->id, $secondStage->id)
        ->assertHasNoErrors();

    $lead->refresh();

    expect($lead->funnel_stage_id)->toBe($secondStage->id);
    expect(TimelineEvent::query()
        ->where('lead_id', $lead->id)
        ->where('event_type', 'funnel_moved')
        ->exists())->toBeTrue();
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
        ->call('requestMoveLead', $lead->id, $lostStage->id)
        ->assertSet('showLostModal', true);

    Livewire::actingAs($agent)
        ->test(FunnelBoard::class)
        ->set('pendingLeadId', $lead->id)
        ->set('pendingStageId', $lostStage->id)
        ->set('lostReasonId', LostReason::query()->where('slug', 'bought-competitor-product')->value('id'))
        ->call('confirmLostMove')
        ->assertHasNoErrors();

    $lead->refresh();

    expect($lead->funnel_stage_id)->toBe($lostStage->id)
        ->and($lead->status)->toBe(LeadStatus::New)
        ->and($lead->lost_reason_id)->toBe(LostReason::query()->where('slug', 'bought-competitor-product')->value('id'))
        ->and($lead->lost_reason)->toBe('Bought Competitor Product');
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
    ]);

    Livewire::actingAs($agent)
        ->test(FunnelBoard::class)
        ->set('funnelId', salesFunnelId())
        ->call('moveLead', $lead->id, $wonStage->id);

    $lead = $lead->fresh(['stage', 'funnel']);

    expect($lead->lifecycle)->toBe(LeadLifecycle::Client)
        ->and($lead->status)->toBe(LeadStatus::Customer)
        ->and($lead->funnel?->slug)->toBe('after-sales-funnel')
        ->and($lead->stage?->slug)->toBe('warranty-registration');
});

it('filters the board by lifecycle', function () {
    $agent = funnelAgent();
    $salesFunnel = Funnel::query()->where('slug', 'sales-funnel')->first();
    $stage = FunnelStage::query()
        ->where('funnel_id', $salesFunnel->id)
        ->orderBy('sort_order')
        ->first();

    Lead::factory()->assignedTo($agent)->create([
        'lifecycle' => LeadLifecycle::Lead,
        'funnel_id' => $stage->funnel_id,
        'funnel_stage_id' => $stage->id,
        'first_name' => 'Visible',
        'last_name' => 'Lead',
    ]);

    Lead::factory()->assignedTo($agent)->prospect()->create([
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
            ->call('moveLead', $lead->id, $targetStageId);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
        // Scoped lookup blocks access to another user's lead.
    }

    expect($lead->fresh()->funnel_stage_id)->toBe($stageId);
});
