<?php

use App\Enums\Crm\LeadLifecycle;
use App\Livewire\Crm\FunnelBoard;
use App\Livewire\Crm\FunnelManager;
use App\Livewire\Crm\LeadForm;
use App\Models\Crm\Funnel;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Lead;
use App\Models\Crm\PipelineStageHistory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\File;
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

    $recruit = \App\Models\Crm\Recruit::factory()->assignedTo($agent)->create([
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
        ->and(PipelineStageHistory::query()
            ->where('contact_type', 'lead')
            ->where('contact_id', $lead->id)
            ->count())->toBe(1);
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

    $lead = \App\Models\Crm\Recruit::query()->where('email', 'recruit@example.com')->first();

    expect($lead)->not->toBeNull()
        ->and($lead->funnel?->slug)->toBe('recruiting-funnel')
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

it('shows update seeder for super admins and exports current funnels', function () {
    $superAdmin = superAdminUser();
    $path = database_path('seeders/FunnelsSeeder.php');
    $original = File::get($path);

    FunnelStage::query()
        ->where('slug', 'new-lead')
        ->update(['name' => 'Fresh Lead Export']);

    try {
        Livewire::actingAs($superAdmin)
            ->test(FunnelManager::class)
            ->assertSee('Update Seeder')
            ->call('updateSeeder')
            ->assertHasNoErrors()
            ->assertSee('FunnelsSeeder.php updated');

        expect(File::get($path))
            ->toContain('Fresh Lead Export')
            ->toContain('sales-funnel')
            ->toContain('class FunnelsSeeder');
    } finally {
        File::put($path, $original);
    }
});

it('hides update seeder from non super admins', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('slug', 'admin')->first());

    Livewire::actingAs($admin)
        ->test(FunnelManager::class)
        ->assertDontSee('Update Seeder');
});

it('lists funnels and deletes an unused pipeline', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('slug', 'admin')->first());

    $recruiting = Funnel::query()->where('slug', 'recruiting-funnel')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(FunnelManager::class)
        ->assertSee('Pipelines')
        ->assertSee('Retail Sales Funnel')
        ->assertSee('Recruiting Funnel')
        ->call('deleteFunnel', $recruiting->id)
        ->assertHasNoErrors()
        ->assertSee('Pipeline deleted.');

    expect(Funnel::query()->where('slug', 'recruiting-funnel')->exists())->toBeFalse();
});

it('blocks deleting the default funnel or a funnel with records', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('slug', 'admin')->first());

    $sales = Funnel::query()->where('slug', 'sales-funnel')->firstOrFail();
    $stage = FunnelStage::query()->where('funnel_id', $sales->id)->orderBy('sort_order')->firstOrFail();

    Lead::factory()->assignedTo($admin)->create([
        'funnel_id' => $sales->id,
        'funnel_stage_id' => $stage->id,
    ]);

    $component = Livewire::actingAs($admin)->test(FunnelManager::class);

    $component->call('deleteFunnel', $sales->id)
        ->assertHasErrors('funnel');

    expect(Funnel::query()->whereKey($sales->id)->exists())->toBeTrue();

    $referral = Funnel::query()->where('slug', 'referral-funnel')->firstOrFail();
    $referralStage = FunnelStage::query()->where('funnel_id', $referral->id)->orderBy('sort_order')->firstOrFail();

    Lead::factory()->assignedTo($admin)->create([
        'funnel_id' => $referral->id,
        'funnel_stage_id' => $referralStage->id,
    ]);

    Livewire::actingAs($admin)
        ->test(FunnelManager::class)
        ->call('deleteFunnel', $referral->id)
        ->assertHasErrors('funnel');

    expect(Funnel::query()->whereKey($referral->id)->exists())->toBeTrue();
});
