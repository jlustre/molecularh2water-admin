<?php

use App\Enums\Crm\LeadLifecycle;
use App\Models\Crm\Lead;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

function crmAgent(string $name): User
{
    $user = User::factory()->create(['name' => $name]);
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

it('limits agents to their own assigned leads prospects and clients', function () {
    $agentA = crmAgent('Agent A');
    $agentB = crmAgent('Agent B');

    Lead::query()->create([
        'lifecycle' => LeadLifecycle::Prospect,
        'status' => 'new',
        'temperature' => 'warm',
        'first_name' => 'Visible',
        'last_name' => 'Prospect',
        'email' => 'visible-agent-a@example.com',
        'assigned_user_id' => $agentA->id,
        'consent_given' => true,
    ]);

    Lead::query()->create([
        'lifecycle' => LeadLifecycle::Prospect,
        'status' => 'new',
        'temperature' => 'warm',
        'first_name' => 'Hidden',
        'last_name' => 'Prospect',
        'email' => 'hidden-agent-b@example.com',
        'assigned_user_id' => $agentB->id,
        'consent_given' => true,
    ]);

    $this->actingAs($agentA)
        ->get(route('portal.crm.prospects.index'))
        ->assertOk()
        ->assertSee('Visible Prospect')
        ->assertDontSee('Hidden Prospect');

    $this->actingAs($agentB)
        ->get(route('portal.crm.prospects.index'))
        ->assertOk()
        ->assertSee('Hidden Prospect')
        ->assertDontSee('Visible Prospect');
});

it('does not show unassigned web prospects to agents', function () {
    $agent = crmAgent('Solo Agent');

    Lead::query()->create([
        'lifecycle' => LeadLifecycle::Prospect,
        'status' => 'new',
        'temperature' => 'warm',
        'first_name' => 'Unassigned',
        'last_name' => 'Web Lead',
        'email' => 'unassigned@example.com',
        'assigned_user_id' => null,
        'consent_given' => true,
    ]);

    $this->actingAs($agent)
        ->get(route('portal.crm.prospects.index'))
        ->assertOk()
        ->assertDontSee('Unassigned Web Lead');
});

it('allows admins with view-all permission to see every record', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('slug', 'admin')->first());

    $agent = crmAgent('Assigned Agent');

    Lead::query()->create([
        'lifecycle' => LeadLifecycle::Lead,
        'status' => 'new',
        'temperature' => 'cold',
        'first_name' => 'Assigned',
        'last_name' => 'Lead',
        'email' => 'assigned@example.com',
        'assigned_user_id' => $agent->id,
        'consent_given' => true,
    ]);

    Lead::query()->create([
        'lifecycle' => LeadLifecycle::Lead,
        'status' => 'new',
        'temperature' => 'cold',
        'first_name' => 'Pool',
        'last_name' => 'Lead',
        'email' => 'pool@example.com',
        'assigned_user_id' => null,
        'consent_given' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.crm.leads.index'))
        ->assertOk()
        ->assertSee('Assigned Lead')
        ->assertSee('Pool Lead');
});

it('prevents agents from moving another users lead on the pipeline', function () {
    $agentA = crmAgent('Pipeline Agent A');
    $agentB = crmAgent('Pipeline Agent B');

    $stageId = \App\Models\Crm\FunnelStage::query()->orderBy('sort_order')->value('id');
    $targetStageId = \App\Models\Crm\FunnelStage::query()->orderByDesc('sort_order')->value('id');

    $lead = Lead::query()->create([
        'lifecycle' => LeadLifecycle::Lead,
        'status' => 'new',
        'temperature' => 'warm',
        'first_name' => 'Protected',
        'last_name' => 'Lead',
        'email' => 'protected@example.com',
        'assigned_user_id' => $agentB->id,
        'funnel_id' => \App\Models\Crm\FunnelStage::query()->whereKey($stageId)->value('funnel_id'),
        'funnel_stage_id' => $stageId,
        'consent_given' => true,
    ]);

    try {
        Livewire::actingAs($agentA)
            ->test(\App\Livewire\Crm\FunnelBoard::class)
            ->call('moveLead', $lead->id, $targetStageId);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
        // Scoped lookup blocks access to another user's lead.
    }

    expect($lead->fresh()->funnel_stage_id)->toBe($stageId);
});

it('allows co-owners to access a shared lead', function () {
    $agentA = crmAgent('Co-owner A');
    $agentB = crmAgent('Co-owner B');

    $lead = Lead::query()->create([
        'lifecycle' => LeadLifecycle::Prospect,
        'business_line' => 'h2s',
        'status' => 'new',
        'temperature' => 'warm',
        'first_name' => 'Shared',
        'last_name' => 'Prospect',
        'email' => 'shared-coowned@example.com',
        'assigned_user_id' => $agentA->id,
        'consent_given' => true,
    ]);
    $lead->owners()->sync([$agentA->id, $agentB->id]);

    expect($lead->isAccessibleBy($agentA))->toBeTrue()
        ->and($lead->isAccessibleBy($agentB))->toBeTrue();

    $this->actingAs($agentB)
        ->get(route('portal.crm.prospects.index'))
        ->assertOk()
        ->assertSee('Shared Prospect');
});

it('scopes dashboard stats to the current user', function () {
    $agentA = crmAgent('Stats Agent A');
    $agentB = crmAgent('Stats Agent B');

    Lead::query()->create([
        'lifecycle' => LeadLifecycle::Lead,
        'status' => 'new',
        'temperature' => 'cold',
        'first_name' => 'Mine',
        'email' => 'mine@example.com',
        'assigned_user_id' => $agentA->id,
        'consent_given' => true,
    ]);

    Lead::query()->create([
        'lifecycle' => LeadLifecycle::Lead,
        'status' => 'new',
        'temperature' => 'cold',
        'first_name' => 'Theirs',
        'email' => 'theirs@example.com',
        'assigned_user_id' => $agentB->id,
        'consent_given' => true,
    ]);

    Livewire::actingAs($agentA)
        ->test(\App\Livewire\Crm\DashboardStats::class)
        ->assertSet('totalLeads', 1);
});
