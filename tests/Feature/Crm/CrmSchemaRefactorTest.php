<?php

use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Lifecycle;
use Database\Seeders\CrmSeeder;
use Database\Seeders\RolesSeeder;

beforeEach(function () {
    $this->seed([RolesSeeder::class, CrmSeeder::class]);
});

it('creates a lead record with lifecycle_id', function () {
    $lead = Lead::factory()->create(['email' => 'schema-test@example.com']);

    expect($lead->lifecycleSlug())->toBe(\App\Enums\Crm\LeadLifecycle::Lead)
        ->and($lead->lifecycle_id)->toBe(Lifecycle::idFor('lead'));
});

it('creates a prospect in prospects table', function () {
    $prospect = Prospect::factory()->create(['email' => 'prospect-schema@example.com']);

    expect($prospect->lifecycleSlug())->toBe(\App\Enums\Crm\LeadLifecycle::Prospect)
        ->and(Lead::query()->where('email', 'prospect-schema@example.com')->exists())->toBeFalse();
});
