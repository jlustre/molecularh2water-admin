<?php

use App\Models\Crm\FunnelStage;
use App\Models\Role;
use App\Models\User;
use App\Services\Crm\DashboardStatsService;
use App\Support\Crm\PipelineSummaryGrouper;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

it('groups default funnel stages into configured pipeline summary sections', function () {
    $stages = FunnelStage::query()
        ->whereHas('funnel', fn ($query) => $query->whereIn('slug', config('crm.pipeline_summary_funnel_slugs', ['sales-funnel'])))
        ->orderBy('sort_order')
        ->get()
        ->each(fn (FunnelStage $stage, int $index) => $stage->setAttribute('leads_count', $index === 0 ? 2 : 0));

    $groups = app(PipelineSummaryGrouper::class)->group($stages);

    expect($groups)->toHaveCount(6)
        ->and(collect($groups)->pluck('label')->all())->toBe(['Early', 'Demo', 'Sales', 'Fulfillment', 'Close', 'Referrals'])
        ->and($groups[0]['stages']->pluck('slug')->all())->toBe(['new-lead', 'contacted', 'qualified'])
        ->and($groups[1]['stages']->pluck('slug')->all())->toContain('demo-scheduled')
        ->and($groups[4]['stages']->pluck('slug')->all())->toContain('closed-won', 'closed-lost')
        ->and($groups[5]['stages']->pluck('slug')->all())->toContain('referral-received');
});

it('places unconfigured stages in an other group', function () {
    $stage = new FunnelStage([
        'slug' => 'custom-stage',
        'name' => 'Custom Stage',
    ]);
    $stage->setAttribute('leads_count', 1);

    $groups = app(PipelineSummaryGrouper::class)->group(collect([$stage]));

    expect($groups)->toHaveCount(1)
        ->and($groups[0]['label'])->toBe('Other')
        ->and($groups[0]['stages']->first()->slug)->toBe('custom-stage');
});

it('exposes grouped funnel stages from dashboard stats', function () {
    $consultant = User::factory()->create();
    $consultant->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $stats = app(DashboardStatsService::class)->get($consultant);

    expect($stats)->toHaveKey('groupedFunnelStages')
        ->and($stats['groupedFunnelStages'])->not->toBeEmpty()
        ->and($stats['groupedFunnelStages'][0]['label'])->toBe('Early');
});
