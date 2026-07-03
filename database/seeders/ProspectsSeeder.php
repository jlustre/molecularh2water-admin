<?php

namespace Database\Seeders;

use App\Enums\BusinessLine;
use App\Enums\Crm\LeadLifecycle;
use App\Enums\Crm\LeadStatus;
use App\Enums\Crm\LeadTemperature;
use App\Models\Crm\Activity;
use App\Models\Crm\ActivityType;
use App\Models\Crm\Funnel;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\LeadSource;
use App\Models\Crm\Lifecycle;
use App\Models\Crm\Prospect;
use App\Models\Crm\LostReason;
use App\Models\Crm\Note;
use App\Models\Crm\Tag;
use App\Models\Crm\Team;
use App\Models\Crm\TimelineEvent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProspectsSeeder extends Seeder
{
    /**
     * Seed named prospect scenarios across funnel stages for CRM QA.
     *
     * Run after: RolesSeeder, CrmSeeder, CrmUsersSeeder
     * Command: php artisan db:seed --class=ProspectsSeeder
     */
    public function run(): void
    {
        $funnel = Funnel::query()->where('is_default', true)->first();
        $stages = FunnelStage::query()
            ->when($funnel, fn ($query) => $query->where('funnel_id', $funnel->id))
            ->get()
            ->keyBy('slug');

        if ($stages->isEmpty()) {
            $this->command?->warn('ProspectsSeeder: no funnel stages found. Run CrmSeeder first.');

            return;
        }

        $this->seedProspectProfileTags();

        $sources = LeadSource::query()->get()->keyBy('slug');
        $profileTags = Tag::query()
            ->whereIn('slug', collect(config('crm.prospect_profile_tags', []))->map(fn (string $name) => Str::slug($name)))
            ->get()
            ->keyBy('slug');
        $activityTypes = ActivityType::query()->get()->keyBy('slug');
        $lostReasons = LostReason::query()->get()->keyBy('slug');
        $agents = $this->resolveAgents();
        $team = Team::query()->where('slug', 'sales-team')->first();
        $manager = $agents->get('manager@crm.demo');

        if ($team && $manager && $agents->isNotEmpty()) {
            $sync = $agents
                ->except('manager@crm.demo')
                ->mapWithKeys(fn (User $agent) => [$agent->id => ['role' => 'member']])
                ->all();
            $sync[$manager->id] = ['role' => 'lead'];
            $team->update(['manager_id' => $manager->id]);
            $team->users()->sync($sync);
        }

        $fallbackAgent = $agents->first() ?? User::query()->whereHas('roles', fn ($q) => $q->where('slug', 'consultant'))->first();

        if (! $fallbackAgent) {
            $this->command?->warn('ProspectsSeeder: no CRM agents found. Run CrmUsersSeeder first.');

            return;
        }

        foreach ($this->scenarios() as $scenario) {
            $stage = $stages->get($scenario['stage_slug']);

            if (! $stage) {
                continue;
            }

            $ownerEmails = $scenario['owner_emails'] ?? (
                isset($scenario['agent_email']) ? array_filter([$scenario['agent_email']]) : []
            );
            $owners = collect($ownerEmails)
                ->map(fn (string $email) => $agents->get($email))
                ->filter()
                ->values();
            $primaryOwner = $owners->first();

            $source = $sources->get($scenario['source_slug'] ?? 'referral')
                ?? $sources->first();

            $lostReason = isset($scenario['lost_reason_slug'])
                ? $lostReasons->get($scenario['lost_reason_slug'])
                : null;

            $lead = Prospect::query()->updateOrCreate(
                ['email' => $scenario['email']],
                [
                    'lifecycle_id' => Lifecycle::idFor(LeadLifecycle::Prospect),
                    'business_line' => $scenario['business_line'] ?? BusinessLine::H2s->value,
                    'status' => $scenario['status']->value,
                    'temperature' => $scenario['temperature']->value,
                    'score' => $scenario['score'],
                    'first_name' => $scenario['first_name'],
                    'last_name' => $scenario['last_name'],
                    'phone' => $scenario['phone'],
                    'address' => $scenario['address'] ?? null,
                    'city' => $scenario['city'] ?? null,
                    'state' => $scenario['state'] ?? null,
                    'company' => $scenario['company'] ?? null,
                    'occupation' => $scenario['occupation'] ?? null,
                    'spouse_name' => $scenario['spouse_name'] ?? null,
                    'spouse_occupation' => $scenario['spouse_occupation'] ?? null,
                    'best_time_to_contact' => $scenario['best_time_to_contact'] ?? null,
                    'message' => $scenario['notes'] ?? null,
                    'lead_source_id' => $source?->id,
                    'funnel_id' => $stage->funnel_id,
                    'funnel_stage_id' => $stage->id,
                    'assigned_user_id' => $primaryOwner?->id,
                    'team_id' => $team?->id,
                    'lost_reason_id' => $lostReason?->id,
                    'lost_reason' => $lostReason?->requires_detail
                        ? ($scenario['lost_reason_detail'] ?? 'Seeded lost detail.')
                        : ($lostReason?->name),
                    'last_contacted_at' => $scenario['last_contacted_at'] ?? null,
                    'next_follow_up_at' => $scenario['next_follow_up_at'] ?? null,
                    'consent_given' => true,
                    'metadata' => [
                        'seed' => 'prospects',
                        'scenario' => $scenario['scenario_key'],
                        'label' => $scenario['label'],
                    ],
                ],
            );

            if ($owners->isNotEmpty()) {
                $lead->owners()->sync($owners->pluck('id')->all());
            } else {
                $lead->owners()->detach();
            }

            if (! empty($scenario['tag_slugs'])) {
                $tagIds = collect($scenario['tag_slugs'])
                    ->map(fn (string $slug) => $profileTags->get($slug)?->id)
                    ->filter()
                    ->values()
                    ->all();

                $lead->tags()->sync($tagIds);
            }

            $activityAgent = $primaryOwner ?? $fallbackAgent;
            $this->seedActivities($lead, $activityAgent, $activityTypes, $scenario['activities'] ?? []);
            $this->seedNote($lead, $activityAgent, $scenario['note'] ?? null);
            $this->seedTimeline($lead, $activityAgent, $scenario['label']);
        }

        $this->command?->info('ProspectsSeeder: seeded '.count($this->scenarios()).' prospect scenarios.');
    }

    /**
     * @return \Illuminate\Support\Collection<string, User>
     */
    private function resolveAgents()
    {
        return User::query()
            ->whereIn('email', ['agent1@crm.demo', 'agent2@crm.demo', 'agent3@crm.demo', 'manager@crm.demo'])
            ->orderBy('id')
            ->get()
            ->keyBy('email');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function scenarios(): array
    {
        return [
            [
                'scenario_key' => 'new-lead-unassigned',
                'label' => 'New — unassigned inbound',
                'email' => 'prospect.new-lead@crm.demo',
                'first_name' => 'Nina',
                'last_name' => 'Brooks',
                'phone' => '555-1001',
                'stage_slug' => 'new-lead',
                'status' => LeadStatus::New,
                'temperature' => LeadTemperature::Cold,
                'score' => 15,
                'source_slug' => 'website',
                'owner_emails' => [],
                'city' => 'Austin',
                'state' => 'TX',
                'best_time_to_contact' => 'evening',
                'notes' => 'Submitted interest form on the website.',
            ],
            [
                'scenario_key' => 'contacted-warm',
                'label' => 'Contacted — warm follow-up',
                'email' => 'prospect.contacted@crm.demo',
                'first_name' => 'Marcus',
                'last_name' => 'Chen',
                'phone' => '555-1002',
                'stage_slug' => 'contacted',
                'status' => LeadStatus::Contacting,
                'temperature' => LeadTemperature::Warm,
                'score' => 35,
                'source_slug' => 'cold-call',
                'owner_emails' => ['agent1@crm.demo'],
                'business_line' => BusinessLine::Hcc->value,
                'last_contacted_at' => now()->subDays(1),
                'next_follow_up_at' => now()->addDay(),
                'activities' => [
                    ['type' => 'phone-call', 'title' => 'Intro call', 'days_ago' => 1],
                ],
            ],
            [
                'scenario_key' => 'demo-scheduled-hot',
                'label' => 'Demo scheduled — hot prospect',
                'email' => 'prospect.show-booked@crm.demo',
                'first_name' => 'Elena',
                'last_name' => 'Vasquez',
                'phone' => '555-1003',
                'stage_slug' => 'demo-scheduled',
                'status' => LeadStatus::Engaged,
                'temperature' => LeadTemperature::Hot,
                'score' => 72,
                'source_slug' => 'cooking-show',
                'owner_emails' => ['agent1@crm.demo'],
                'business_line' => BusinessLine::Hcc->value,
                'address' => '742 Oak Lane',
                'city' => 'Dallas',
                'state' => 'TX',
                'occupation' => 'Nurse',
                'spouse_name' => 'Carlos Vasquez',
                'spouse_occupation' => 'Contractor',
                'best_time_to_contact' => 'weekends',
                'tag_slugs' => ['married', 'homeowner', 'health-conscious'],
                'next_follow_up_at' => now()->addDays(2),
                'activities' => [
                    ['type' => 'cooking-show', 'title' => 'Cooking show RSVP', 'days_ago' => 2],
                    ['type' => 'text-message', 'title' => 'Sent show reminder', 'days_ago' => 0],
                ],
                'note' => 'Confirmed Saturday cooking show attendance. Spouse will join.',
            ],
            [
                'scenario_key' => 'demo-completed',
                'label' => 'Demo completed — evaluating',
                'email' => 'prospect.show-completed@crm.demo',
                'first_name' => 'David',
                'last_name' => 'Nguyen',
                'phone' => '555-1004',
                'stage_slug' => 'demo-completed',
                'status' => LeadStatus::AttendedDemo,
                'temperature' => LeadTemperature::Warm,
                'score' => 68,
                'source_slug' => 'water-awareness-show',
                'owner_emails' => ['agent2@crm.demo'],
                'business_line' => BusinessLine::H2s->value,
                'last_contacted_at' => now()->subDays(2),
                'next_follow_up_at' => now()->addDays(1),
                'activities' => [
                    ['type' => 'water-awareness-show', 'title' => 'Attended water awareness show', 'days_ago' => 2],
                    ['type' => 'follow-up', 'title' => 'Post-show check-in', 'days_ago' => 1],
                ],
            ],
            [
                'scenario_key' => 'follow-up-considering',
                'label' => 'Follow-up — considering purchase',
                'email' => 'prospect.follow-up@crm.demo',
                'first_name' => 'Rachel',
                'last_name' => 'Morgan',
                'phone' => '555-1005',
                'stage_slug' => 'follow-up',
                'status' => LeadStatus::Considering,
                'temperature' => LeadTemperature::Warm,
                'score' => 58,
                'source_slug' => 'referral',
                'owner_emails' => ['agent2@crm.demo'],
                'tag_slugs' => ['25-years-old', 'business-minded'],
                'notes' => 'Wants to compare financing options before deciding.',
                'activities' => [
                    ['type' => 'email', 'title' => 'Sent product comparison', 'days_ago' => 3],
                    ['type' => 'phone-call', 'title' => 'Pricing discussion', 'days_ago' => 1],
                ],
            ],
            [
                'scenario_key' => 'order-submitted-negotiating',
                'label' => 'Order submitted — negotiating terms',
                'email' => 'prospect.order-started@crm.demo',
                'first_name' => 'James',
                'last_name' => 'Whitfield',
                'phone' => '555-1006',
                'stage_slug' => 'order-submitted',
                'status' => LeadStatus::Negotiating,
                'temperature' => LeadTemperature::Hot,
                'score' => 85,
                'source_slug' => 'referral',
                'owner_emails' => ['agent3@crm.demo'],
                'address' => '88 Pine Street',
                'city' => 'Houston',
                'state' => 'TX',
                'occupation' => 'Small business owner',
                'tag_slugs' => ['homeowner', 'business-minded'],
                'next_follow_up_at' => now()->addHours(6),
                'activities' => [
                    ['type' => 'home-demo', 'title' => 'In-home product demo', 'days_ago' => 4],
                    ['type' => 'order-placed', 'title' => 'Draft order started', 'days_ago' => 0],
                ],
            ],
            [
                'scenario_key' => 'ready-to-buy',
                'label' => 'Ready to buy — closing soon',
                'email' => 'prospect.ready-to-buy@crm.demo',
                'first_name' => 'Amanda',
                'last_name' => 'Foster',
                'phone' => '555-1007',
                'stage_slug' => 'ready-to-purchase',
                'status' => LeadStatus::ReadyToBuy,
                'temperature' => LeadTemperature::Hot,
                'score' => 92,
                'source_slug' => 'landing-page',
                'owner_emails' => ['manager@crm.demo'],
                'best_time_to_contact' => 'morning',
                'tag_slugs' => ['married', 'w-dep-children', 'homeowner'],
                'next_follow_up_at' => now()->addHours(2),
                'activities' => [
                    ['type' => 'phone-call', 'title' => 'Final terms call', 'days_ago' => 0],
                ],
                'note' => 'Ready to sign this week pending spouse approval.',
            ],
            [
                'scenario_key' => 'closed-lost-price',
                'label' => 'Closed lost — price too high',
                'email' => 'prospect.closed-lost@crm.demo',
                'first_name' => 'Tyler',
                'last_name' => 'Reed',
                'phone' => '555-1008',
                'stage_slug' => 'closed-lost',
                'status' => LeadStatus::Inactive,
                'temperature' => LeadTemperature::Cold,
                'score' => 20,
                'source_slug' => 'social-media',
                'owner_emails' => ['agent3@crm.demo'],
                'lost_reason_slug' => 'price-too-high',
                'last_contacted_at' => now()->subDays(5),
                'activities' => [
                    ['type' => 'phone-call', 'title' => 'Final objection call', 'days_ago' => 5],
                ],
                'note' => 'Loved the product but budget did not allow purchase this quarter.',
            ],
            [
                'scenario_key' => 'closed-lost-competitor',
                'label' => 'Closed lost — bought competitor',
                'email' => 'prospect.lost-competitor@crm.demo',
                'first_name' => 'Priya',
                'last_name' => 'Sharma',
                'phone' => '555-1009',
                'stage_slug' => 'closed-lost',
                'status' => LeadStatus::Inactive,
                'temperature' => LeadTemperature::Cold,
                'score' => 10,
                'source_slug' => 'cold-call',
                'owner_emails' => ['agent2@crm.demo'],
                'lost_reason_slug' => 'bought-competitor-product',
                'activities' => [
                    ['type' => 'email', 'title' => 'Sent final follow-up', 'days_ago' => 7],
                ],
            ],
            [
                'scenario_key' => 'no-response-pipeline',
                'label' => 'No response — stalled in pipeline',
                'email' => 'prospect.no-response@crm.demo',
                'first_name' => 'Chris',
                'last_name' => 'Dalton',
                'phone' => '555-1010',
                'stage_slug' => 'contacted',
                'status' => LeadStatus::Active,
                'temperature' => LeadTemperature::Cold,
                'score' => 25,
                'source_slug' => 'website',
                'owner_emails' => ['agent1@crm.demo'],
                'next_follow_up_at' => now()->subDay(),
                'activities' => [
                    ['type' => 'phone-call', 'title' => 'No answer — left VM', 'days_ago' => 3],
                    ['type' => 'text-message', 'title' => 'Follow-up text', 'days_ago' => 1],
                ],
            ],
            [
                'scenario_key' => 'shared-agent1-agent2',
                'label' => 'Shared — co-owned by Agent 1 & Agent 2',
                'email' => 'prospect.shared-agent1-2@crm.demo',
                'first_name' => 'Olivia',
                'last_name' => 'Grant',
                'phone' => '555-1011',
                'stage_slug' => 'follow-up',
                'status' => LeadStatus::Considering,
                'temperature' => LeadTemperature::Warm,
                'score' => 55,
                'source_slug' => 'referral',
                'owner_emails' => ['agent1@crm.demo', 'agent2@crm.demo'],
                'business_line' => BusinessLine::Hcc->value,
                'notes' => 'Joint referral — both agents are working this prospect.',
                'activities' => [
                    ['type' => 'phone-call', 'title' => 'Joint intro call', 'days_ago' => 2],
                ],
            ],
            [
                'scenario_key' => 'shared-agent2-agent3',
                'label' => 'Shared — co-owned by Agent 2 & Agent 3',
                'email' => 'prospect.shared-agent2-3@crm.demo',
                'first_name' => 'Daniel',
                'last_name' => 'Brooks',
                'phone' => '555-1012',
                'stage_slug' => 'demo-completed',
                'status' => LeadStatus::AttendedDemo,
                'temperature' => LeadTemperature::Hot,
                'score' => 70,
                'source_slug' => 'water-awareness-show',
                'owner_emails' => ['agent2@crm.demo', 'agent3@crm.demo'],
                'business_line' => BusinessLine::H2s->value,
                'last_contacted_at' => now()->subDay(),
                'next_follow_up_at' => now()->addDays(2),
                'activities' => [
                    ['type' => 'water-awareness-show', 'title' => 'Co-hosted show', 'days_ago' => 3],
                    ['type' => 'follow-up', 'title' => 'Shared follow-up plan', 'days_ago' => 1],
                ],
                'note' => 'Both agents attended the demo and share follow-up duties.',
            ],
        ];
    }

    private function seedProspectProfileTags(): void
    {
        foreach (config('crm.prospect_profile_tags', []) as $name) {
            Tag::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            );
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<string, ActivityType>  $activityTypes
     * @param  list<array{type: string, title: string, days_ago: int}>  $activities
     */
    private function seedActivities(Prospect $lead, User $agent, $activityTypes, array $activities): void
    {
        foreach ($activities as $activity) {
            $type = $activityTypes->get($activity['type']);

            if (! $type) {
                continue;
            }

            $completedAt = now()->subDays($activity['days_ago']);

            Activity::query()->updateOrCreate(
                [
                    'contact_type' => $lead->getMorphClass(),
                    'contact_id' => $lead->id,
                    'title' => $activity['title'],
                    'completed_at' => $completedAt,
                ],
                [
                    'activity_type_id' => $type->id,
                    'user_id' => $agent->id,
                    'business_line' => $lead->business_line,
                    'description' => 'Seeded activity for '.$lead->fullName().'.',
                    'outcome' => 'connected',
                ],
            );
        }
    }

    private function seedNote(Prospect $lead, User $agent, ?string $body): void
    {
        if (blank($body)) {
            return;
        }

        Note::query()->updateOrCreate(
            [
                'noteable_type' => $lead->getMorphClass(),
                'noteable_id' => $lead->id,
                'body' => $body,
            ],
            ['user_id' => $agent->id],
        );
    }

    private function seedTimeline(Prospect $lead, User $agent, string $label): void
    {
        TimelineEvent::query()->updateOrCreate(
            [
                'contact_type' => $lead->getMorphClass(),
                'contact_id' => $lead->id,
                'event_type' => 'prospect_seeded',
                'title' => 'Demo prospect seeded',
            ],
            [
                'user_id' => $agent->id,
                'description' => $label,
                'properties' => ['seed' => 'prospects'],
                'created_at' => $lead->created_at ?? now(),
            ],
        );
    }
}
