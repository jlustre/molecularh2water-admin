<?php

namespace Database\Seeders;

use App\Enums\Crm\DeliveryStatus;
use App\Enums\Crm\DemonstrationOutcome;
use App\Enums\Crm\DemonstrationStatus;
use App\Enums\Crm\DemonstrationType;
use App\Enums\Crm\InstallationStatus;
use App\Enums\Crm\LeadLifecycle;
use App\Enums\Crm\LeadStatus;
use App\Enums\Crm\LeadTemperature;
use App\Enums\Crm\OrderStatus;
use App\Enums\Crm\PaymentStatus;
use App\Enums\Crm\QuotationStatus;
use App\Enums\Crm\ReferralStatus;
use App\Models\Crm\CalendarEvent;
use App\Models\Crm\CalendarEventType;
use App\Models\Crm\Consultation;
use App\Models\Crm\CrmProduct;
use App\Models\Crm\Delivery;
use App\Models\Crm\Demonstration;
use App\Models\Crm\FollowupSequence;
use App\Models\Crm\FollowupSequenceEnrollment;
use App\Models\Crm\Funnel;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Installation;
use App\Models\Crm\LeadSource;
use App\Models\Crm\Lifecycle;
use App\Models\Crm\Order;
use App\Models\Crm\OrderItem;
use App\Models\Crm\PipelineStageHistory;
use App\Models\Crm\Quotation;
use App\Models\Crm\QuotationItem;
use App\Models\Crm\Referral;
use App\Models\Crm\Team;
use App\Models\User;
use App\Support\Crm\CrmContactResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class CrmPremiumDemoSeeder extends Seeder
{
    /**
     * Seed premium CRM modules (demos, quotes, orders, referrals, automation history)
     * for manual QA and executive dashboard testing.
     *
     * Requires: RolesSeeder, CrmSeeder, CalendarSeeder, CrmUsersSeeder, CrmMarketingSeeder
     *
     * Command: php artisan db:seed --class=CrmPremiumDemoSeeder
     *
     * Demo logins (password: Password123):
     * - manager@crm.demo — manager (view-all reports & executive dashboard)
     * - agent1@crm.demo — Alex Rivera (consultant)
     *
     * Named QA prospects (search by email in CRM):
     * - qa.automation.demo-ready@crm.demo — schedule a demo to fire automation
     * - qa.automation.order-pay@crm.demo — record payment to fire automation
     * - qa.automation.delivery@crm.demo — complete delivery to fire automation
     * - qa.demo.scheduled@crm.demo — pre-built scheduled demo + calendar event
     * - qa.quote.presented@crm.demo — consultation + presented quote (convert to order)
     * - qa.order.fulfilled@crm.demo — paid order with delivery + installation
     * - qa.client.referrer@crm.demo — client with referral leaderboard data
     * - qa.executive.revenue@crm.demo — paid orders for revenue analytics
     */
    public function run(): void
    {
        $automationWasEnabled = config('crm.automation.enabled', true);
        config(['crm.automation.enabled' => false]);

        $funnel = Funnel::query()->where('is_default', true)->first();
        $stages = FunnelStage::query()
            ->when($funnel, fn ($q) => $q->where('funnel_id', $funnel->id))
            ->get()
            ->keyBy('slug');

        if ($stages->isEmpty()) {
            $this->command?->warn('CrmPremiumDemoSeeder: run CrmSeeder first.');

            return;
        }

        $agent = User::query()->where('email', 'agent1@crm.demo')->first()
            ?? User::query()->whereHas('roles', fn ($q) => $q->where('slug', 'consultant'))->first();

        if (! $agent) {
            $this->command?->warn('CrmPremiumDemoSeeder: run CrmUsersSeeder first.');

            return;
        }

        $manager = User::query()->where('email', 'manager@crm.demo')->first();
        $team = Team::query()->where('slug', 'sales-team')->first();
        $source = LeadSource::query()->where('slug', 'referral')->first()
            ?? LeadSource::query()->first();
        $product = CrmProduct::query()->orderBy('sort_order')->first();
        $demoEventType = CalendarEventType::query()->where('slug', 'home-demo')->first()
            ?? CalendarEventType::query()->orderBy('sort_order')->first();

        $this->seedAutomationTriggers($agent, $team, $source, $stages, $funnel, $product);
        $this->seedDemonstrationScenarios($agent, $team, $source, $stages, $funnel, $demoEventType);
        $this->seedQuotationScenario($agent, $team, $source, $stages, $funnel, $product);
        $this->seedFulfillmentScenario($agent, $team, $source, $stages, $funnel, $product);
        $this->seedReferralScenario($agent, $team, $source, $stages, $funnel, $manager);
        $this->seedExecutiveRevenueScenario($agent, $team, $source, $stages, $funnel, $product);
        $this->seedPipelineHistories($agent, $stages, $funnel);
        $this->seedSequenceEnrollments($agent);

        config(['crm.automation.enabled' => $automationWasEnabled]);

        $this->command?->info('CrmPremiumDemoSeeder: premium QA scenarios ready.');
        $this->command?->line('  Search prospects by email prefix: qa.automation.* / qa.demo.* / qa.quote.* / qa.order.* / qa.client.* / qa.executive.*');
        $this->command?->line('  Login: agent1@crm.demo or manager@crm.demo (Password123)');
        $this->command?->line('  Re-enable live automation: CRM_AUTOMATION_ENABLED=true in .env');
    }

    /**
     * @param  Collection<string, FunnelStage>  $stages
     */
    private function seedAutomationTriggers(
        User $agent,
        ?Team $team,
        ?LeadSource $source,
        Collection $stages,
        ?Funnel $funnel,
        ?CrmProduct $product,
    ): void {
        $this->upsertLead('qa.automation.demo-ready@crm.demo', [
            'first_name' => 'Morgan',
            'last_name' => 'DemoReady',
            'stage' => $stages->get('qualified'),
            'agent' => $agent,
            'team' => $team,
            'source' => $source,
            'funnel' => $funnel,
            'temperature' => LeadTemperature::Hot,
            'status' => LeadStatus::Engaged,
            'notes' => 'QA: Schedule a demo from the profile to test automation (calendar, notify, sequence).',
        ]);

        $orderPayLead = $this->upsertLead('qa.automation.order-pay@crm.demo', [
            'first_name' => 'Olivia',
            'last_name' => 'OrderPay',
            'stage' => $stages->get('order-submitted'),
            'agent' => $agent,
            'team' => $team,
            'source' => $source,
            'funnel' => $funnel,
            'temperature' => LeadTemperature::Hot,
            'status' => LeadStatus::ReadyToBuy,
            'notes' => 'QA: Record payment on the order panel to test delivery-scheduling automation.',
        ]);

        if ($orderPayLead && $product) {
            $this->upsertOrder($orderPayLead, $agent, $product, [
                'order_number' => 'QA-AUTO-ORDER-01',
                'status' => OrderStatus::Submitted,
                'payment_status' => PaymentStatus::Pending,
                'total' => 3499,
                'amount_paid' => 0,
                'submitted_at' => now()->subDay(),
            ]);
        }

        $deliveryLead = $this->upsertLead('qa.automation.delivery@crm.demo', [
            'first_name' => 'Dylan',
            'last_name' => 'Delivery',
            'stage' => $stages->get('payment-received'),
            'agent' => $agent,
            'team' => $team,
            'source' => $source,
            'funnel' => $funnel,
            'lifecycle' => LeadLifecycle::Prospect,
            'temperature' => LeadTemperature::Hot,
            'status' => LeadStatus::Customer,
            'notes' => 'QA: Complete delivery on the orders panel to test orientation-task automation.',
        ]);

        if ($deliveryLead && $product) {
            $order = $this->upsertOrder($deliveryLead, $agent, $product, [
                'order_number' => 'QA-AUTO-ORDER-02',
                'status' => OrderStatus::Confirmed,
                'payment_status' => PaymentStatus::Paid,
                'total' => 2199,
                'amount_paid' => 2199,
                'paid_at' => now()->subDays(2),
                'submitted_at' => now()->subDays(3),
            ]);

            if ($order) {
                Delivery::query()->updateOrCreate(
                    ['order_id' => $order->id],
                    array_merge($this->contactRef($deliveryLead), [
                        'user_id' => $agent->id,
                        'status' => DeliveryStatus::Scheduled,
                        'scheduled_at' => now()->addDay(),
                        'address' => $deliveryLead->address,
                        'contact_name' => $deliveryLead->fullName(),
                        'contact_phone' => $deliveryLead->phone,
                        'checklist' => collect(config('crm.delivery_checklist', []))
                            ->mapWithKeys(fn (string $label, string $slug) => [$slug => false])
                            ->all(),
                    ]),
                );
            }
        }
    }

    /**
     * @param  Collection<string, FunnelStage>  $stages
     */
    private function seedDemonstrationScenarios(
        User $agent,
        ?Team $team,
        ?LeadSource $source,
        Collection $stages,
        ?Funnel $funnel,
        ?CalendarEventType $eventType,
    ): void {
        $scheduledLead = $this->upsertLead('qa.demo.scheduled@crm.demo', [
            'first_name' => 'Casey',
            'last_name' => 'ScheduledDemo',
            'stage' => $stages->get('demo-scheduled'),
            'agent' => $agent,
            'team' => $team,
            'source' => $source,
            'funnel' => $funnel,
            'temperature' => LeadTemperature::Hot,
            'status' => LeadStatus::AttendedDemo,
            'notes' => 'QA: Pre-seeded scheduled demonstration with linked calendar event.',
        ]);

        if ($scheduledLead) {
            $scheduledAt = now()->addDays(2)->setHour(14)->setMinute(0);
            $demo = Demonstration::query()->updateOrCreate(
                array_merge($this->contactRef($scheduledLead), ['status' => DemonstrationStatus::Scheduled]),
                [
                    'user_id' => $agent->id,
                    'type' => DemonstrationType::Home,
                    'scheduled_at' => $scheduledAt,
                    'duration_minutes' => 90,
                    'venue' => '123 Demo Lane',
                    'notes' => 'Seeded scheduled demo for QA.',
                ],
            );

            if ($eventType) {
                $event = CalendarEvent::query()->updateOrCreate(
                    [
                        'related_type' => $scheduledLead->getMorphClass(),
                        'related_id' => $scheduledLead->id,
                        'title' => 'Demo: '.$scheduledLead->fullName(),
                    ],
                    [
                        'user_id' => $agent->id,
                        'team_id' => $team?->id,
                        'calendar_event_type_id' => $eventType->id,
                        'start_at' => $scheduledAt,
                        'end_at' => $scheduledAt->copy()->addMinutes(90),
                        'location' => '123 Demo Lane',
                        'created_by' => $agent->id,
                        'updated_by' => $agent->id,
                        'metadata' => ['seed' => 'premium-demo', 'demonstration_id' => $demo->id],
                    ],
                );
                $demo->update(['calendar_event_id' => $event->id]);
            }
        }

        $completedLead = $this->upsertLead('qa.demo.completed@crm.demo', [
            'first_name' => 'Riley',
            'last_name' => 'CompletedDemo',
            'stage' => $stages->get('interested'),
            'agent' => $agent,
            'team' => $team,
            'source' => $source,
            'funnel' => $funnel,
            'temperature' => LeadTemperature::Hot,
            'status' => LeadStatus::Considering,
            'notes' => 'QA: Completed demo with interested outcome — check post-demo task on timeline.',
        ]);

        if ($completedLead) {
            Demonstration::query()->updateOrCreate(
                array_merge($this->contactRef($completedLead), ['status' => DemonstrationStatus::Completed]),
                [
                    'user_id' => $agent->id,
                    'type' => DemonstrationType::Home,
                    'outcome' => DemonstrationOutcome::Interested,
                    'scheduled_at' => now()->subDays(3),
                    'duration_minutes' => 60,
                    'attended' => true,
                    'notes' => 'Great engagement — wants financing options.',
                ],
            );
        }

        $soldLead = $this->upsertLead('qa.demo.sold@crm.demo', [
            'first_name' => 'Avery',
            'last_name' => 'SoldDemo',
            'stage' => $stages->get('ready-to-purchase'),
            'agent' => $agent,
            'team' => $team,
            'source' => $source,
            'funnel' => $funnel,
            'temperature' => LeadTemperature::Hot,
            'status' => LeadStatus::ReadyToBuy,
            'notes' => 'QA: Demo completed with sold outcome.',
        ]);

        if ($soldLead) {
            Demonstration::query()->updateOrCreate(
                array_merge($this->contactRef($soldLead), ['status' => DemonstrationStatus::Completed]),
                [
                    'user_id' => $agent->id,
                    'type' => DemonstrationType::Home,
                    'outcome' => DemonstrationOutcome::Sold,
                    'scheduled_at' => now()->subWeek(),
                    'duration_minutes' => 75,
                    'attended' => true,
                ],
            );
        }
    }

    /**
     * @param  Collection<string, FunnelStage>  $stages
     */
    private function seedQuotationScenario(
        User $agent,
        ?Team $team,
        ?LeadSource $source,
        Collection $stages,
        ?Funnel $funnel,
        ?CrmProduct $product,
    ): void {
        $lead = $this->upsertLead('qa.quote.presented@crm.demo', [
            'first_name' => 'Quinn',
            'last_name' => 'QuoteReady',
            'stage' => $stages->get('quote-presented'),
            'agent' => $agent,
            'team' => $team,
            'source' => $source,
            'funnel' => $funnel,
            'temperature' => LeadTemperature::Hot,
            'status' => LeadStatus::Negotiating,
            'notes' => 'QA: Presented quotation — use Convert to Order on quotations panel.',
        ]);

        if (! $lead || ! $product) {
            return;
        }

        $consultation = Consultation::query()->updateOrCreate(
            $this->contactRef($lead),
            [
                'user_id' => $agent->id,
                'customer_needs' => 'Family of 4, wants countertop H2 unit with installation.',
                'product_recommendation' => $product->name,
                'family_size' => 4,
                'water_consumption' => 'High',
                'budget' => 4000,
                'financing_option' => '12-month plan',
                'health_goals' => 'Anti-inflammatory hydration',
                'objections' => 'Price vs bottled water',
                'final_recommendation' => $product->name,
                'conducted_at' => now()->subDays(2),
            ],
        );

        $quotation = Quotation::query()->updateOrCreate(
            ['quote_number' => 'QA-Q-0001'],
            array_merge($this->contactRef($lead), [
                'user_id' => $agent->id,
                'consultation_id' => $consultation->id,
                'status' => QuotationStatus::Presented,
                'subtotal' => 3499,
                'total' => 3499,
                'valid_until' => now()->addDays(14),
                'presented_at' => now()->subDay(),
                'warranty_notes' => '5-year manufacturer warranty',
                'financing_notes' => '0% for 12 months available',
            ]),
        );

        QuotationItem::query()->updateOrCreate(
            ['quotation_id' => $quotation->id, 'description' => $product->name],
            [
                'crm_product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 3499,
                'line_total' => 3499,
                'sort_order' => 1,
            ],
        );
    }

    /**
     * @param  Collection<string, FunnelStage>  $stages
     */
    private function seedFulfillmentScenario(
        User $agent,
        ?Team $team,
        ?LeadSource $source,
        Collection $stages,
        ?Funnel $funnel,
        ?CrmProduct $product,
    ): void {
        $lead = $this->upsertLead('qa.order.fulfilled@crm.demo', [
            'first_name' => 'Harper',
            'last_name' => 'Fulfilled',
            'stage' => $stages->get('delivered-installed'),
            'agent' => $agent,
            'team' => $team,
            'source' => $source,
            'funnel' => $funnel,
            'lifecycle' => LeadLifecycle::Client,
            'temperature' => LeadTemperature::Hot,
            'status' => LeadStatus::Customer,
            'converted_at' => now()->subMonth(),
            'notes' => 'QA: Full fulfillment path — paid order, delivered, installed.',
        ]);

        if (! $lead || ! $product) {
            return;
        }

        $order = $this->upsertOrder($lead, $agent, $product, [
            'order_number' => 'QA-ORD-FULL-01',
            'status' => OrderStatus::Fulfilled,
            'payment_status' => PaymentStatus::Paid,
            'total' => 3698,
            'amount_paid' => 3698,
            'payment_method' => 'Wire',
            'paid_at' => now()->subDays(10),
            'submitted_at' => now()->subDays(12),
        ]);

        if (! $order) {
            return;
        }

        $delivery = Delivery::query()->updateOrCreate(
            ['order_id' => $order->id],
            array_merge($this->contactRef($lead), [
                'user_id' => $agent->id,
                'status' => DeliveryStatus::Delivered,
                'scheduled_at' => now()->subDays(8),
                'delivered_at' => now()->subDays(7),
                'address' => $lead->address ?? '456 Install Ave',
                'contact_name' => $lead->fullName(),
                'contact_phone' => $lead->phone,
                'checklist' => collect(config('crm.delivery_checklist', []))
                    ->mapWithKeys(fn (string $label, string $slug) => [$slug => true])
                    ->all(),
            ]),
        );

        Installation::query()->updateOrCreate(
            ['order_id' => $order->id],
            array_merge($this->contactRef($lead), [
                'delivery_id' => $delivery->id,
                'user_id' => $agent->id,
                'status' => InstallationStatus::Completed,
                'scheduled_at' => now()->subDays(6),
                'completed_at' => now()->subDays(5),
                'checklist' => collect(config('crm.installation_checklist', []))
                    ->mapWithKeys(fn (string $label, string $slug) => [$slug => true])
                    ->all(),
            ]),
        );
    }

    /**
     * @param  Collection<string, FunnelStage>  $stages
     */
    private function seedReferralScenario(
        User $agent,
        ?Team $team,
        ?LeadSource $source,
        Collection $stages,
        ?Funnel $funnel,
        ?User $manager,
    ): void {
        $afterSalesFunnel = Funnel::query()->where('slug', config('crm.after_sales_funnel_slug', 'after-sales-funnel'))->first();
        $warrantyStage = $afterSalesFunnel
            ? FunnelStage::query()->where('funnel_id', $afterSalesFunnel->id)->where('slug', 'warranty-registration')->first()
            : null;

        $client = $this->upsertLead('qa.client.referrer@crm.demo', [
            'first_name' => 'Blake',
            'last_name' => 'Referrer',
            'stage' => $warrantyStage ?? $stages->get('closed-won'),
            'agent' => $agent,
            'team' => $team,
            'source' => $source,
            'funnel' => $afterSalesFunnel ?? $funnel,
            'lifecycle' => LeadLifecycle::Client,
            'temperature' => LeadTemperature::Hot,
            'status' => LeadStatus::Customer,
            'converted_at' => now()->subMonths(2),
            'notes' => 'QA: Client with referrals — check Referrals panel and reports leaderboard.',
        ]);

        if (! $client) {
            return;
        }

        $referralFunnel = Funnel::query()->where('slug', config('crm.referral_funnel_slug', 'referral-funnel'))->first();
        $referralStage = $referralFunnel
            ? FunnelStage::query()->where('funnel_id', $referralFunnel->id)->where('slug', 'referral-received')->first()
            : $stages->get('new-lead');

        $convertedReferral = $this->upsertLead('qa.referral.converted@crm.demo', [
            'first_name' => 'Cameron',
            'last_name' => 'ReferredWon',
            'stage' => $stages->get('closed-won') ?? $referralStage,
            'agent' => $manager ?? $agent,
            'team' => $team,
            'source' => $source,
            'funnel' => $funnel,
            'lifecycle' => LeadLifecycle::Client,
            'referred_by' => $client,
            'temperature' => LeadTemperature::Warm,
            'status' => LeadStatus::Customer,
        ]);

        if ($convertedReferral) {
            Referral::query()->updateOrCreate(
                [
                    'referred_type' => $convertedReferral->getMorphClass(),
                    'referred_id' => $convertedReferral->id,
                ],
                [
                    'referrer_type' => $client->getMorphClass(),
                    'referrer_id' => $client->id,
                    'user_id' => $agent->id,
                    'status' => ReferralStatus::Converted,
                    'notes' => 'Referred by Blake — closed won.',
                ],
            );
        }

        $pendingReferral = $this->upsertLead('qa.referral.pending@crm.demo', [
            'first_name' => 'Drew',
            'last_name' => 'ReferredPending',
            'stage' => $referralStage,
            'agent' => $agent,
            'team' => $team,
            'source' => $source,
            'funnel' => $referralFunnel ?? $funnel,
            'lifecycle' => LeadLifecycle::Prospect,
            'referred_by' => $client,
            'temperature' => LeadTemperature::Cold,
            'status' => LeadStatus::New,
        ]);

        if ($pendingReferral) {
            Referral::query()->updateOrCreate(
                [
                    'referred_type' => $pendingReferral->getMorphClass(),
                    'referred_id' => $pendingReferral->id,
                ],
                [
                    'referrer_type' => $client->getMorphClass(),
                    'referrer_id' => $client->id,
                    'user_id' => $agent->id,
                    'status' => ReferralStatus::Pending,
                ],
            );
        }

        $this->upsertLead('qa.aftersales@crm.demo', [
            'first_name' => 'Emery',
            'last_name' => 'AfterSales',
            'stage' => $warrantyStage ?? $stages->get('customer-orientation'),
            'agent' => $agent,
            'team' => $team,
            'source' => $source,
            'funnel' => $afterSalesFunnel ?? $funnel,
            'lifecycle' => LeadLifecycle::Client,
            'temperature' => LeadTemperature::Warm,
            'status' => LeadStatus::Customer,
            'converted_at' => now()->subWeeks(3),
            'notes' => 'QA: After-sales funnel client — warranty registration stage.',
        ]);
    }

    /**
     * @param  Collection<string, FunnelStage>  $stages
     */
    private function seedExecutiveRevenueScenario(
        User $agent,
        ?Team $team,
        ?LeadSource $source,
        Collection $stages,
        ?Funnel $funnel,
        ?CrmProduct $product,
    ): void {
        if (! $product) {
            return;
        }

        $products = CrmProduct::query()->orderBy('sort_order')->limit(3)->get();

        foreach ([
            ['email' => 'qa.executive.revenue@crm.demo', 'first' => 'Finley', 'last' => 'RevenueA', 'amount' => 3499, 'days_ago' => 5],
            ['email' => 'qa.executive.revenue-b@crm.demo', 'first' => 'Gray', 'last' => 'RevenueB', 'amount' => 2199, 'days_ago' => 18],
            ['email' => 'qa.executive.revenue-c@crm.demo', 'first' => 'Hayden', 'last' => 'RevenueC', 'amount' => 1899, 'days_ago' => 45],
        ] as $index => $row) {
            $lead = $this->upsertLead($row['email'], [
                'first_name' => $row['first'],
                'last_name' => $row['last'],
                'stage' => $stages->get('payment-received') ?? $stages->get('delivered-installed'),
                'agent' => $agent,
                'team' => $team,
                'source' => $source,
                'funnel' => $funnel,
                'lifecycle' => LeadLifecycle::Client,
                'temperature' => LeadTemperature::Hot,
                'status' => LeadStatus::Customer,
                'notes' => 'QA: Paid order for executive dashboard revenue charts.',
            ]);

            if (! $lead) {
                continue;
            }

            $lineProduct = $products->get($index % $products->count()) ?? $product;

            $this->upsertOrder($lead, $agent, $lineProduct, [
                'order_number' => 'QA-REV-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'status' => OrderStatus::Confirmed,
                'payment_status' => PaymentStatus::Paid,
                'total' => $row['amount'],
                'amount_paid' => $row['amount'],
                'payment_method' => 'Card',
                'paid_at' => now()->subDays($row['days_ago']),
                'submitted_at' => now()->subDays($row['days_ago'] + 1),
            ]);
        }
    }

    /**
     * @param  Collection<string, FunnelStage>  $stages
     */
    private function seedPipelineHistories(User $agent, Collection $stages, ?Funnel $funnel): void
    {
        if (! $funnel) {
            return;
        }

        $lead = CrmContactResolver::queryFor(LeadLifecycle::Prospect)
            ->where('email', 'qa.demo.completed@crm.demo')
            ->first();

        if (! $lead) {
            return;
        }

        $path = [
            ['from' => 'new-lead', 'to' => 'contacted', 'days' => 2],
            ['from' => 'contacted', 'to' => 'qualified', 'days' => 3],
            ['from' => 'qualified', 'to' => 'demo-scheduled', 'days' => 4],
            ['from' => 'demo-scheduled', 'to' => 'demo-completed', 'days' => 5],
            ['from' => 'demo-completed', 'to' => 'interested', 'days' => 1],
        ];

        $cursor = now()->subDays(20);

        foreach ($path as $hop) {
            $from = $stages->get($hop['from']);
            $to = $stages->get($hop['to']);

            if (! $from || ! $to) {
                continue;
            }

            PipelineStageHistory::query()->updateOrCreate(
                array_merge(
                    $this->contactRef($lead),
                    [
                        'from_stage_id' => $from->id,
                        'to_stage_id' => $to->id,
                    ],
                ),
                [
                    'funnel_id' => $funnel->id,
                    'user_id' => $agent->id,
                    'duration_in_previous_stage_seconds' => $hop['days'] * 86400,
                    'created_at' => $cursor,
                    'updated_at' => $cursor,
                ],
            );

            $cursor = $cursor->addDays($hop['days']);
        }
    }

    private function seedSequenceEnrollments(User $agent): void
    {
        $nurture = FollowupSequence::query()->where('slug', 'new-prospect-nurture')->first();
        $demoSequence = FollowupSequence::query()->where('slug', 'demo-reminder-sequence')->first();

        $capturedLead = CrmContactResolver::queryFor(LeadLifecycle::Prospect)
            ->where('email', 'qa.automation.demo-ready@crm.demo')
            ->first();

        if ($nurture && $capturedLead) {
            FollowupSequenceEnrollment::query()->updateOrCreate(
                array_merge(
                    [
                        'followup_sequence_id' => $nurture->id,
                        'trigger_event' => 'prospect_captured',
                    ],
                    $this->contactRef($capturedLead),
                ),
                [
                    'user_id' => $agent->id,
                    'status' => 'completed',
                    'current_step_order' => 3,
                    'completed_at' => now()->subDays(2),
                ],
            );
        }

        $scheduledLead = CrmContactResolver::queryFor(LeadLifecycle::Prospect)
            ->where('email', 'qa.demo.scheduled@crm.demo')
            ->first();

        if ($demoSequence && $scheduledLead) {
            FollowupSequenceEnrollment::query()->updateOrCreate(
                array_merge(
                    [
                        'followup_sequence_id' => $demoSequence->id,
                        'trigger_event' => 'demonstration.scheduled',
                    ],
                    $this->contactRef($scheduledLead),
                ),
                [
                    'user_id' => $agent->id,
                    'status' => 'active',
                    'current_step_order' => 1,
                    'next_step_at' => now()->addHour(),
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function upsertLead(string $email, array $data): ?Model
    {
        $lifecycle = $data['lifecycle'] ?? LeadLifecycle::Prospect;
        $class = CrmContactResolver::modelClassFor($lifecycle);
        $stage = $data['stage'] ?? null;
        $funnel = $data['funnel'] ?? null;
        $referredBy = $data['referred_by'] ?? null;

        return $class::query()->updateOrCreate(
            ['email' => $email],
            [
                'lifecycle_id' => Lifecycle::idFor($lifecycle),
                'business_line' => $data['business_line'] ?? 'h2s',
                'status' => ($data['status'] ?? LeadStatus::New)->value,
                'temperature' => ($data['temperature'] ?? LeadTemperature::Warm)->value,
                'score' => $data['score'] ?? 70,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? fake()->numerify('555-####'),
                'address' => $data['address'] ?? fake()->streetAddress(),
                'city' => $data['city'] ?? fake()->city(),
                'state' => $data['state'] ?? fake()->stateAbbr(),
                'lead_source_id' => ($data['source'] ?? null)?->id,
                'funnel_id' => $funnel?->id ?? $stage?->funnel_id,
                'funnel_stage_id' => $stage?->id,
                'assigned_user_id' => ($data['agent'] ?? null)?->id,
                'team_id' => ($data['team'] ?? null)?->id,
                'referred_by_type' => $referredBy?->getMorphClass(),
                'referred_by_id' => $referredBy?->id,
                'message' => $data['notes'] ?? null,
                'consent_given' => true,
                'converted_at' => $data['converted_at'] ?? null,
                'metadata' => array_merge(['seed' => 'premium-demo'], $data['metadata'] ?? []),
            ],
        );
    }

    /**
     * @return array{contact_type: string, contact_id: int}
     */
    private function contactRef(Model $contact): array
    {
        return [
            'contact_type' => $contact->getMorphClass(),
            'contact_id' => $contact->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function upsertOrder(Model $contact, User $agent, CrmProduct $product, array $data): ?Order
    {
        $order = Order::query()->updateOrCreate(
            ['order_number' => $data['order_number']],
            array_merge($this->contactRef($contact), [
                'user_id' => $agent->id,
                'status' => ($data['status'] ?? OrderStatus::Submitted)->value,
                'payment_status' => ($data['payment_status'] ?? PaymentStatus::Pending)->value,
                'subtotal' => $data['total'] ?? $product->unit_price,
                'total' => $data['total'] ?? $product->unit_price,
                'amount_paid' => $data['amount_paid'] ?? 0,
                'payment_method' => $data['payment_method'] ?? null,
                'submitted_at' => $data['submitted_at'] ?? now(),
                'paid_at' => $data['paid_at'] ?? null,
            ]),
        );

        OrderItem::query()->updateOrCreate(
            ['order_id' => $order->id, 'description' => $product->name],
            [
                'crm_product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => $data['total'] ?? $product->unit_price,
                'line_total' => $data['total'] ?? $product->unit_price,
                'sort_order' => 1,
            ],
        );

        return $order;
    }
}
