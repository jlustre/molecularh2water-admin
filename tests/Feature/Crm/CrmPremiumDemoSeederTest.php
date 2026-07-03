<?php

use App\Models\Crm\Consultation;
use App\Models\Crm\Delivery;
use App\Models\Crm\Demonstration;
use App\Models\Crm\FollowupSequenceEnrollment;
use App\Models\Crm\Installation;
use App\Models\Crm\Lead;
use App\Models\Crm\Order;
use App\Models\Crm\PipelineStageHistory;
use App\Models\Crm\Quotation;
use App\Models\Crm\Referral;
use Database\Seeders\CalendarSeeder;
use Database\Seeders\CrmMarketingSeeder;
use Database\Seeders\CrmPremiumDemoSeeder;
use Database\Seeders\CrmSeeder;
use Database\Seeders\CrmUsersSeeder;
use Database\Seeders\RolesSeeder;

beforeEach(function () {
    $this->seed([
        RolesSeeder::class,
        CrmSeeder::class,
        CalendarSeeder::class,
        CrmUsersSeeder::class,
        CrmMarketingSeeder::class,
        CrmPremiumDemoSeeder::class,
    ]);
});

it('seeds named automation trigger scenarios', function () {
    expect(Lead::query()->where('email', 'qa.automation.demo-ready@crm.demo')->exists())->toBeTrue()
        ->and(Lead::query()->where('email', 'qa.automation.order-pay@crm.demo')->exists())->toBeTrue()
        ->and(Lead::query()->where('email', 'qa.automation.delivery@crm.demo')->exists())->toBeTrue()
        ->and(Order::query()->where('order_number', 'QA-AUTO-ORDER-01')->exists())->toBeTrue();
});

it('seeds demonstration and quotation scenarios', function () {
    expect(Demonstration::query()->whereHas('lead', fn ($q) => $q->where('email', 'qa.demo.scheduled@crm.demo'))->exists())->toBeTrue()
        ->and(Quotation::query()->where('quote_number', 'QA-Q-0001')->exists())->toBeTrue()
        ->and(Consultation::query()->whereHas('lead', fn ($q) => $q->where('email', 'qa.quote.presented@crm.demo'))->exists())->toBeTrue();
});

it('seeds fulfillment and referral scenarios', function () {
    expect(Order::query()->where('order_number', 'QA-ORD-FULL-01')->exists())->toBeTrue()
        ->and(Delivery::query()->whereHas('order', fn ($q) => $q->where('order_number', 'QA-ORD-FULL-01'))->exists())->toBeTrue()
        ->and(Installation::query()->whereHas('order', fn ($q) => $q->where('order_number', 'QA-ORD-FULL-01'))->exists())->toBeTrue()
        ->and(Referral::query()->whereHas('referrer', fn ($q) => $q->where('email', 'qa.client.referrer@crm.demo'))->count())->toBeGreaterThanOrEqual(2);
});

it('seeds executive analytics and sequence enrollment data', function () {
    expect(Order::query()->where('payment_status', 'paid')->where('order_number', 'like', 'QA-REV-%')->count())->toBe(3)
        ->and(PipelineStageHistory::query()->whereHas('lead', fn ($q) => $q->where('email', 'qa.demo.completed@crm.demo'))->count())->toBeGreaterThanOrEqual(3)
        ->and(FollowupSequenceEnrollment::query()->count())->toBeGreaterThanOrEqual(2);
});

it('is idempotent when run twice', function () {
    $this->seed(CrmPremiumDemoSeeder::class);

    expect(Lead::query()->where('email', 'like', 'qa.%')->count())->toBeGreaterThanOrEqual(10)
        ->and(Order::query()->where('order_number', 'like', 'QA-%')->count())->toBeGreaterThanOrEqual(5);
});
