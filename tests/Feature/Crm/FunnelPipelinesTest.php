<?php

use App\Enums\Crm\LeadLifecycle;
use App\Livewire\Crm\FunnelBoard;
use App\Livewire\Crm\LeadForm;
use App\Models\Crm\Funnel;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Lead;
use App\Models\Crm\PipelineStageHistory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

it('seeds all six documented funnels with expected stage counts', function () {
    $expected = [
        'sales-funnel' => 21,
        'recruiting-funnel' => 7,
        'customer-onboarding-funnel' => 5,
        'referral-funnel' => 5,
        'after-sales-funnel' => 10,
        'corporate-sales-funnel' => 6,
    ];

    foreach ($expected as $slug => $count) {
        $funnel = Funnel::query()->where('slug', $slug)->first();

        expect($funnel)->not->toBeNull("Missing funnel: {$slug}")
            ->and($funnel->stages()->count())->toBe($count, "Unexpected stage count for {$slug}");
    }
});

it('loads recruiting funnel stages on the pipeline board', function () {
    $agent = User::factory()->create();
    $agent->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $recruitingFunnel = Funnel::query()->where('slug', 'recruiting-funnel')->first();
    $firstStage = FunnelStage::query()
        ->where('funnel_id', $recruitingFunnel->id)
        ->orderBy('sort_order')
        ->first();

    $lead = Lead::factory()->assignedTo($agent)->create([
        'lifecycle' => LeadLifecycle::Recruit,
        'funnel_id' => $recruitingFunnel->id,
        'funnel_stage_id' => $firstStage->id,
        'first_name' => 'Recruit',
        'last_name' => 'Candidate',
    ]);

    Livewire::actingAs($agent)
        ->test(FunnelBoard::class)
        ->set('funnelId', $recruitingFunnel->id)
        ->assertSee('Prospecting')
        ->assertSee('Recruit Candidate');
});

it('records pipeline history when lead form changes funnel stage', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('slug', 'admin')->first());

    $from = FunnelStage::query()->where('slug', 'new-lead')->first();
    $to = FunnelStage::query()->where('slug', 'contacted')->where('funnel_id', $from->funnel_id)->first();

    $lead = Lead::factory()->assignedTo($admin)->create([
        'funnel_id' => $from->funnel_id,
        'funnel_stage_id' => $from->id,
    ]);

    Livewire::actingAs($admin)
        ->test(LeadForm::class, ['lead' => $lead])
        ->set('funnel_stage_id', $to->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($lead->fresh()->stage?->slug)->toBe('contacted')
        ->and(PipelineStageHistory::query()->where('lead_id', $lead->id)->count())->toBe(1);
});

it('defaults recruits to the recruiting funnel on create', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('slug', 'admin')->first());

    $recruitingFunnelId = Funnel::query()->where('slug', 'recruiting-funnel')->value('id');

    Livewire::actingAs($admin)
        ->test(LeadForm::class)
        ->set('lifecycle', LeadLifecycle::Recruit)
        ->set('funnel_id', $recruitingFunnelId)
        ->set('first_name', 'New')
        ->set('email', 'recruit@example.com')
        ->call('save')
        ->assertHasNoErrors();

    $lead = Lead::query()->where('email', 'recruit@example.com')->first();

    expect($lead->funnel?->slug)->toBe('recruiting-funnel')
        ->and($lead->stage?->slug)->toBe('prospecting');
});

it('scopes lead form stages to the selected pipeline', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('slug', 'admin')->first());

    $corporateFunnel = Funnel::query()->where('slug', 'corporate-sales-funnel')->first();

    Livewire::actingAs($admin)
        ->test(LeadForm::class)
        ->set('funnel_id', $corporateFunnel->id)
        ->assertSee('Inquiry')
        ->assertDontSee('New Lead')
        ->assertDontSee('VIP Customer');
});
