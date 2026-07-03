<?php

use App\Models\Crm\Consultation;
use App\Models\Crm\CrmProduct;
use App\Models\Crm\Demonstration;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Quotation;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

it('records a consultation and moves lead to consultation stage', function () {
    $agent = User::factory()->create();
    $agent->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $lead = \App\Models\Crm\Lead::factory()->assignedTo($agent)->prospect()->create([
        'funnel_id' => FunnelStage::query()->where('slug', 'interested')->value('funnel_id'),
        'funnel_stage_id' => FunnelStage::query()->where('slug', 'interested')->value('id'),
    ]);

    Livewire::actingAs($agent)
        ->test(\App\Livewire\Crm\LeadConsultationsPanel::class, ['lead' => $lead])
        ->call('toggleForm')
        ->set('customer_needs', 'Wants whole-home hydrogen water')
        ->set('product_recommendation', 'Molecular H2 Ultra Pro')
        ->set('final_recommendation', 'Recommend Ultra Pro with annual filters')
        ->call('saveConsultation')
        ->assertHasNoErrors();

    expect(Consultation::query()->where('lead_id', $lead->id)->count())->toBe(1)
        ->and($lead->fresh()->stage?->slug)->toBe('consultation');
});

it('creates a quotation with line items and presents it', function () {
    $agent = User::factory()->create();
    $agent->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $product = CrmProduct::query()->first();
    $lead = \App\Models\Crm\Lead::factory()->assignedTo($agent)->prospect()->create([
        'funnel_id' => FunnelStage::query()->where('slug', 'consultation')->value('funnel_id'),
        'funnel_stage_id' => FunnelStage::query()->where('slug', 'consultation')->value('id'),
    ]);

    Livewire::actingAs($agent)
        ->test(\App\Livewire\Crm\LeadQuotationsPanel::class, ['lead' => $lead])
        ->call('toggleBuilder')
        ->set('lineItems.0.crm_product_id', $product->id)
        ->set('lineItems.0.quantity', 1)
        ->call('saveQuotation')
        ->assertHasNoErrors();

    $quotation = Quotation::query()->where('lead_id', $lead->id)->first();

    expect($quotation)->not->toBeNull()
        ->and($quotation->items)->toHaveCount(1)
        ->and((float) $quotation->total)->toBeGreaterThan(0);

    Livewire::actingAs($agent)
        ->test(\App\Livewire\Crm\LeadQuotationsPanel::class, ['lead' => $lead->fresh()])
        ->call('presentQuote', $quotation->id)
        ->assertHasNoErrors();

    expect($quotation->fresh()->status->value)->toBe('presented')
        ->and($lead->fresh()->stage?->slug)->toBe('quote-presented');
});

it('downloads quotation pdf for authorized user', function () {
    $agent = User::factory()->create();
    $agent->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $lead = \App\Models\Crm\Lead::factory()->assignedTo($agent)->prospect()->create();
    $quotation = app(\App\Services\Crm\QuotationService::class)->create(
        $lead,
        ['valid_until' => now()->addDays(30)],
        [[
            'crm_product_id' => CrmProduct::query()->value('id'),
            'description' => 'Test Product',
            'quantity' => 1,
            'unit_price' => 100,
        ]],
        $agent,
    );

    $response = $this->actingAs($agent)
        ->get(route('portal.crm.quotations.pdf', $quotation));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('pdf');
});

it('moves lead to interested when demo completed with interested outcome', function () {
    $agent = User::factory()->create();
    $agent->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $lead = \App\Models\Crm\Lead::factory()->assignedTo($agent)->prospect()->create([
        'funnel_id' => FunnelStage::query()->where('slug', 'demo-scheduled')->value('funnel_id'),
        'funnel_stage_id' => FunnelStage::query()->where('slug', 'demo-scheduled')->value('id'),
    ]);

    $demo = app(\App\Services\Crm\DemonstrationService::class)->schedule($lead, [
        'scheduled_at' => now()->subHour(),
    ], $agent);

    app(\App\Services\Crm\DemonstrationService::class)->complete($demo, [
        'outcome' => 'interested',
        'attended' => true,
    ], $agent);

    expect($demo->fresh()->status->value)->toBe('completed')
        ->and($lead->fresh()->stage?->slug)->toBe('interested');
});

it('moves lead to closed lost when demo completed with not interested outcome', function () {
    $agent = User::factory()->create();
    $agent->roles()->attach(Role::query()->where('slug', 'consultant')->first());

    $lead = \App\Models\Crm\Lead::factory()->assignedTo($agent)->prospect()->create([
        'funnel_id' => FunnelStage::query()->where('slug', 'demo-scheduled')->value('funnel_id'),
        'funnel_stage_id' => FunnelStage::query()->where('slug', 'demo-scheduled')->value('id'),
    ]);

    $demo = Demonstration::query()->create([
        'lead_id' => $lead->id,
        'user_id' => $agent->id,
        'type' => 'home',
        'status' => 'scheduled',
        'scheduled_at' => now(),
        'duration_minutes' => 60,
    ]);

    app(\App\Services\Crm\DemonstrationService::class)->complete($demo, [
        'outcome' => 'not_interested',
        'attended' => true,
    ], $agent);

    expect($lead->fresh()->stage?->slug)->toBe('closed-lost')
        ->and($lead->fresh()->lost_reason_id)->not->toBeNull();
});
