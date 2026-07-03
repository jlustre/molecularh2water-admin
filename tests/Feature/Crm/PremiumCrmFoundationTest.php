<?php

use App\Models\Crm\Demonstration;
use App\Models\Crm\Funnel;
use App\Models\Crm\PipelineStageHistory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

it('seeds multiple pipeline templates', function () {
    expect(Funnel::query()->count())->toBeGreaterThanOrEqual(6)
        ->and(Funnel::query()->where('slug', 'after-sales-funnel')->exists())->toBeTrue()
        ->and(Funnel::query()->where('slug', 'recruiting-funnel')->exists())->toBeTrue();
});

it('records pipeline stage history when moving a lead', function () {
    $agent = User::factory()->create();
    $agent->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $from = \App\Models\Crm\FunnelStage::query()->where('slug', 'new-lead')->first();
    $to = \App\Models\Crm\FunnelStage::query()->where('slug', 'contacted')->first();

    $lead = \App\Models\Crm\Lead::factory()->assignedTo($agent)->create([
        'funnel_id' => $from->funnel_id,
        'funnel_stage_id' => $from->id,
    ]);

    app(\App\Services\Crm\FunnelService::class)->moveLead($lead, $to, $agent);

    expect(PipelineStageHistory::query()->where('lead_id', $lead->id)->count())->toBe(1)
        ->and(PipelineStageHistory::query()->where('lead_id', $lead->id)->value('to_stage_id'))->toBe($to->id);
});

it('schedules a demonstration on a lead', function () {
    $agent = User::factory()->create();
    $agent->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $lead = \App\Models\Crm\Lead::factory()->assignedTo($agent)->prospect()->create();

    Livewire::actingAs($agent)
        ->test(\App\Livewire\Crm\LeadDemonstrationsPanel::class, ['lead' => $lead])
        ->call('toggleScheduleForm')
        ->set('type', 'home')
        ->set('scheduled_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->set('venue', '123 Main St')
        ->call('scheduleDemo')
        ->assertHasNoErrors();

    expect(Demonstration::query()->where('lead_id', $lead->id)->count())->toBe(1);
});

it('suggests calendar follow-up after moving to a demo stage', function () {
    $agent = User::factory()->create();
    $agent->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $from = \App\Models\Crm\FunnelStage::query()->where('slug', 'qualified')->first();
    $to = \App\Models\Crm\FunnelStage::query()->where('slug', 'demo-scheduled')->first();

    $lead = \App\Models\Crm\Lead::factory()->assignedTo($agent)->create([
        'funnel_id' => $from->funnel_id,
        'funnel_stage_id' => $from->id,
    ]);

    Livewire::actingAs($agent)
        ->test(\App\Livewire\Crm\FunnelBoard::class)
        ->call('moveLead', $lead->id, $to->id)
        ->assertSet('showCalendarSuggestion', true)
        ->assertSet('suggestionTitle', 'Scheduled demo');
});
