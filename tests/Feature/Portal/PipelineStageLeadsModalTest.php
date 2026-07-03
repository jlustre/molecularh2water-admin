<?php

use App\Livewire\Portal\Dashboard;
use App\Livewire\Portal\PipelineStageLeadsModal;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Lead;
use App\Models\Role;
use App\Models\User;
use App\Services\Crm\DashboardStatsService;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

function pipelineConsultant(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    return $user;
}

it('renders the pipeline stage leads modal on the dashboard', function () {
    $consultant = pipelineConsultant();

    $this->actingAs($consultant)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Pipeline Summary')
        ->assertSeeLivewire(PipelineStageLeadsModal::class);
});

it('lists leads in the selected stage scoped to the current user', function () {
    $consultant = pipelineConsultant();
    $otherConsultant = pipelineConsultant();
    $stage = FunnelStage::query()->where('slug', 'new-lead')->firstOrFail();

    $visibleLead = Lead::factory()
        ->assignedTo($consultant)
        ->inStage($stage)
        ->create([
            'first_name' => 'Visible',
            'last_name' => 'StageLead',
            'email' => 'visible-stage@example.com',
        ]);

    Lead::factory()
        ->assignedTo($otherConsultant)
        ->inStage($stage)
        ->create([
            'first_name' => 'Hidden',
            'last_name' => 'StageLead',
            'email' => 'hidden-stage@example.com',
        ]);

    Livewire::actingAs($consultant)
        ->test(PipelineStageLeadsModal::class)
        ->dispatch('open-pipeline-stage-leads', stageId: $stage->id)
        ->assertSet('show', true)
        ->assertSet('stageName', $stage->name)
        ->assertSee('Visible StageLead')
        ->assertSee('visible-stage@example.com')
        ->assertSee($consultant->name)
        ->assertDontSee('Hidden StageLead')
        ->assertDontSee('hidden-stage@example.com');
});

it('matches dashboard stats count for leads in a stage', function () {
    $consultant = pipelineConsultant();
    $stage = FunnelStage::query()->where('slug', 'contacted')->firstOrFail();

    Lead::factory()->count(2)->assignedTo($consultant)->inStage($stage)->create();

    $stats = app(DashboardStatsService::class)->get($consultant);
    $stageCount = $stats['funnelStages']->firstWhere('id', $stage->id)?->leads_count;

    expect($stageCount)->toBe(2)
        ->and(app(DashboardStatsService::class)->leadsInStage($stage->id, $consultant))->toHaveCount(2);
});

it('denies access without pipeline and leads permissions', function () {
    $editor = User::factory()->create();
    $editor->roles()->attach(Role::query()->where('slug', 'editor')->first());
    $stage = FunnelStage::query()->where('slug', 'new-lead')->firstOrFail();

    Livewire::actingAs($editor)
        ->test(PipelineStageLeadsModal::class)
        ->dispatch('open-pipeline-stage-leads', stageId: $stage->id)
        ->assertForbidden();
});

it('shows pipeline stage view buttons only for stages with leads', function () {
    $consultant = pipelineConsultant();
    $stage = FunnelStage::query()->where('slug', 'new-lead')->firstOrFail();

    Lead::factory()->assignedTo($consultant)->inStage($stage)->create([
        'first_name' => 'Button',
        'last_name' => 'TestLead',
    ]);

    Livewire::actingAs($consultant)
        ->test(Dashboard::class)
        ->assertSee('Pipeline Summary')
        ->assertSee('open-pipeline-stage-leads')
        ->assertSee('View 1 leads in '.$stage->name);
});

it('closes the pipeline stage leads modal', function () {
    $consultant = pipelineConsultant();
    $stage = FunnelStage::query()->where('slug', 'new-lead')->firstOrFail();

    Livewire::actingAs($consultant)
        ->test(PipelineStageLeadsModal::class)
        ->dispatch('open-pipeline-stage-leads', stageId: $stage->id)
        ->assertSet('show', true)
        ->call('close')
        ->assertSet('show', false)
        ->assertSet('stageId', null);
});
