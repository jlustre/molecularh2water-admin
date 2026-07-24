<?php

use App\Enums\Crm\LeadLifecycle;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\User;
use App\Support\Crm\CrmScope;
use Database\Seeders\CrmSeeder;
use Database\Seeders\CrmUsersSeeder;
use Database\Seeders\ProspectsSeeder;
use Database\Seeders\RolesSeeder;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class, CrmUsersSeeder::class]);
});

it('seeds named prospect scenarios across funnel stages', function () {
    $this->seed(ProspectsSeeder::class);

    expect(Prospect::query()->count())->toBeGreaterThanOrEqual(9);
    expect(Lead::query()->where('email', 'like', 'prospect.%@crm.demo')->count())->toBeGreaterThanOrEqual(3);

    $showBooked = Prospect::query()->where('email', 'prospect.show-booked@crm.demo')->first();

    expect($showBooked)->not->toBeNull()
        ->and($showBooked->stage?->slug)->toBe('demo-scheduled')
        ->and($showBooked->status?->value)->toBe('engaged')
        ->and($showBooked->tags)->not->toBeEmpty()
        ->and($showBooked->activities)->not->toBeEmpty()
        ->and($showBooked->owners)->toHaveCount(1);

    $lost = Prospect::query()->where('email', 'prospect.closed-lost@crm.demo')->first();

    expect($lost)->not->toBeNull()
        ->and($lost->stage?->slug)->toBe('closed-lost')
        ->and($lost->lost_reason_id)->not->toBeNull();

    $unassigned = Lead::query()->where('email', 'prospect.new-lead@crm.demo')->first();

    expect($unassigned)->not->toBeNull()
        ->and($unassigned->lifecycleSlug())->toBe(LeadLifecycle::Lead)
        ->and($unassigned->assigned_user_id)->toBeNull()
        ->and($unassigned->owners)->toBeEmpty()
        ->and($unassigned->stage?->slug)->toBe('new-lead');

    $contacted = Lead::query()->where('email', 'prospect.contacted@crm.demo')->first();

    expect($contacted)->not->toBeNull()
        ->and($contacted->lifecycleSlug())->toBe(LeadLifecycle::Lead)
        ->and($contacted->stage?->slug)->toBe('contacted');

    $shared = Prospect::query()->where('email', 'prospect.shared-agent1-2@crm.demo')->first();

    expect($shared)->not->toBeNull()
        ->and($shared->owners)->toHaveCount(2);
});

it('scopes seeded prospects so agents only see their own unless co-owned', function () {
    $this->seed(ProspectsSeeder::class);

    $agent1 = User::query()->where('email', 'agent1@crm.demo')->firstOrFail();
    $agent2 = User::query()->where('email', 'agent2@crm.demo')->firstOrFail();
    $agent3 = User::query()->where('email', 'agent3@crm.demo')->firstOrFail();

    $agent1Emails = CrmScope::leads(Prospect::query()->where('email', 'like', 'prospect.%@crm.demo'), $agent1)
        ->pluck('email')
        ->merge(CrmScope::leads(Lead::query()->where('email', 'like', 'prospect.%@crm.demo'), $agent1)->pluck('email'))
        ->all();

    expect($agent1Emails)
        ->toContain('prospect.contacted@crm.demo')
        ->toContain('prospect.shared-agent1-2@crm.demo')
        ->not->toContain('prospect.show-completed@crm.demo')
        ->not->toContain('prospect.shared-agent2-3@crm.demo');

    $agent2Emails = CrmScope::leads(Prospect::query()->where('email', 'like', 'prospect.%@crm.demo'), $agent2)
        ->pluck('email')
        ->merge(CrmScope::leads(Lead::query()->where('email', 'like', 'prospect.%@crm.demo'), $agent2)->pluck('email'))
        ->all();

    expect($agent2Emails)
        ->toContain('prospect.show-completed@crm.demo')
        ->toContain('prospect.shared-agent1-2@crm.demo')
        ->toContain('prospect.shared-agent2-3@crm.demo')
        ->not->toContain('prospect.contacted@crm.demo')
        ->not->toContain('prospect.order-started@crm.demo');

    $agent3Emails = CrmScope::leads(Prospect::query()->where('email', 'like', 'prospect.%@crm.demo'), $agent3)
        ->pluck('email')
        ->merge(CrmScope::leads(Lead::query()->where('email', 'like', 'prospect.%@crm.demo'), $agent3)->pluck('email'))
        ->all();

    expect($agent3Emails)
        ->toContain('prospect.order-started@crm.demo')
        ->toContain('prospect.shared-agent2-3@crm.demo')
        ->not->toContain('prospect.contacted@crm.demo')
        ->not->toContain('prospect.shared-agent1-2@crm.demo');
});

it('can be re-run without duplicating seeded prospects', function () {
    $this->seed(ProspectsSeeder::class);
    $firstCount = Prospect::query()->where('email', 'like', 'prospect.%@crm.demo')->count()
        + Lead::query()->where('email', 'like', 'prospect.%@crm.demo')->count();

    $this->seed(ProspectsSeeder::class);
    $secondCount = Prospect::query()->where('email', 'like', 'prospect.%@crm.demo')->count()
        + Lead::query()->where('email', 'like', 'prospect.%@crm.demo')->count();

    expect($secondCount)->toBe($firstCount);
});
