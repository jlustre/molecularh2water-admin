<?php

namespace Database\Seeders;

use App\Enums\Crm\AppointmentStatus;
use App\Enums\Crm\LeadTemperature;
use App\Enums\Crm\TaskPriority;
use App\Enums\Crm\TaskStatus;
use App\Models\Crm\Activity;
use App\Models\Crm\ActivityType;
use App\Models\Crm\Appointment;
use App\Models\Crm\Funnel;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\CalendarEvent;
use App\Models\Crm\CalendarEventType;
use App\Models\Crm\Customer;
use App\Models\Crm\LandingPage;
use App\Models\Crm\Lead;
use App\Models\Crm\LeadSource;
use App\Models\Crm\Note;
use App\Models\Crm\Prospect;
use App\Models\Crm\Tag;
use App\Models\Crm\Task;
use App\Models\Crm\Team;
use App\Models\Crm\TimelineEvent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CrmDemoSeeder extends Seeder
{
    /**
     * Seed realistic CRM transactional data for manual and QA testing.
     *
     * Run after RolesSeeder, CrmSeeder, and CrmUsersSeeder.
     */
    public function run(): void
    {
        $funnel = Funnel::query()->where('is_default', true)->first();
        $stages = FunnelStage::query()->when($funnel, fn ($q) => $q->where('funnel_id', $funnel->id))->orderBy('sort_order')->get();
        $sources = LeadSource::query()->get();
        $tags = Tag::query()->get();
        $activityTypes = ActivityType::query()->get();
        $team = Team::query()->where('slug', 'sales-team')->first();

        $manager = User::query()->where('email', 'manager@crm.demo')->first();
        $agents = User::query()
            ->whereIn('email', ['agent1@crm.demo', 'agent2@crm.demo', 'agent3@crm.demo'])
            ->orderBy('id')
            ->get();

        if ($team && $manager) {
            $team->update(['manager_id' => $manager->id]);
            $sync = $agents->mapWithKeys(fn (User $agent) => [
                $agent->id => ['role' => 'member'],
            ])->all();
            $sync[$manager->id] = ['role' => 'lead'];
            $team->users()->sync($sync);
        }

        if ($agents->isEmpty() || $stages->isEmpty() || $sources->isEmpty()) {
            return;
        }

        $wonStage = $stages->firstWhere('is_won', true);
        $lostStage = $stages->firstWhere('is_lost', true);
        $openStages = $stages->filter(fn (FunnelStage $stage) => ! $stage->is_won && ! $stage->is_lost)->values();

        $leads = collect();

        foreach ($agents as $index => $agent) {
            $leads = $leads->merge($this->seedLeadsForAgent(
                $agent,
                $team,
                $funnel,
                $openStages,
                $wonStage,
                $lostStage,
                $sources,
                $tags,
                $index,
            ));
        }

        $unassigned = Prospect::factory()
            ->count(3)
            ->hot()
            ->fromSource($sources->random())
            ->when($funnel, fn ($factory) => $factory->inStage($openStages->first()))
            ->create([
                'team_id' => $team?->id,
                'next_follow_up_at' => now()->addDay(),
                'metadata' => ['seed' => 'demo', 'pool' => 'unassigned'],
            ]);
        $leads = $leads->merge($unassigned);

        foreach ($leads as $lead) {
            $agent = $lead->assigned_user_id
                ? User::query()->find($lead->assigned_user_id)
                : $agents->random();
            $type = $activityTypes->random();

            Activity::factory()
                ->forLead($lead)
                ->forUser($agent)
                ->create([
                    'activity_type_id' => $type->id,
                    'title' => 'Initial outreach — '.$lead->first_name,
                    'completed_at' => now()->subDays(rand(1, 10)),
                ]);

            if (rand(0, 1)) {
                Activity::factory()
                    ->forLead($lead)
                    ->forUser($agent)
                    ->create([
                        'activity_type_id' => $activityTypes->random()->id,
                        'title' => 'Follow-up touchpoint',
                        'completed_at' => now()->subDays(rand(0, 3)),
                    ]);
            }

            Task::factory()
                ->forLead($lead)
                ->forUser($agent)
                ->create([
                    'title' => 'Follow up with '.$lead->first_name,
                    'priority' => fake()->randomElement([TaskPriority::Normal, TaskPriority::High]),
                    'status' => TaskStatus::Pending,
                    'due_at' => now()->addDays(rand(0, 5)),
                    'reminder_at' => rand(0, 2) === 0 ? now()->subMinute() : null,
                ]);

            if (rand(0, 1)) {
                $starts = rand(0, 1) === 0
                    ? now()->addHours(rand(1, 4))
                    : now()->addDays(rand(1, 7))->setHour(10);

                Appointment::factory()
                    ->forLead($lead)
                    ->forUser($agent)
                    ->create([
                        'title' => 'Home demo — '.$lead->fullName(),
                        'starts_at' => $starts,
                        'ends_at' => $starts->copy()->addHour(),
                        'status' => AppointmentStatus::Scheduled,
                    ]);
            }

            TimelineEvent::query()->create([
                'contact_type' => $lead->getMorphClass(),
                'contact_id' => $lead->id,
                'user_id' => $agent->id,
                'event_type' => 'lead_created',
                'title' => 'Record created',
                'description' => 'Seeded demo record for CRM testing.',
                'properties' => ['seed' => 'demo'],
                'created_at' => $lead->created_at,
            ]);

            if (rand(0, 1)) {
                Note::query()->create([
                    'noteable_type' => $lead->getMorphClass(),
                    'noteable_id' => $lead->id,
                    'user_id' => $agent->id,
                    'body' => 'Demo note: '.$lead->first_name.' is interested in home delivery options.',
                ]);
            }

            if (rand(0, 1)) {
                DB::table('attachments')->insert([
                    'attachable_type' => $lead->getMorphClass(),
                    'attachable_id' => $lead->id,
                    'user_id' => $agent->id,
                    'disk' => 'public',
                    'path' => 'crm/demo/brochure.pdf',
                    'filename' => 'product-brochure.pdf',
                    'mime_type' => 'application/pdf',
                    'size' => 245_760,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $eventTypes = CalendarEventType::query()->get();
        foreach ($agents as $agent) {
            $agentLeads = $leads->where('assigned_user_id', $agent->id);
            foreach (range(1, 3) as $i) {
                $lead = $agentLeads->isNotEmpty() ? $agentLeads->random() : $leads->random();
                $type = $eventTypes->random();
                $start = now()->addDays(rand(-2, 10))->setHour(9 + $i)->setMinute(0);

                $event = CalendarEvent::factory()
                    ->forUser($agent)
                    ->forLead($lead)
                    ->create([
                        'calendar_event_type_id' => $type->id,
                        'title' => $type->name.' — '.$lead->first_name,
                        'start_at' => $start,
                        'end_at' => $start->copy()->addHour(),
                        'team_id' => $team?->id,
                        'created_by' => $agent->id,
                        'updated_by' => $agent->id,
                    ]);

                if ($event->reminder_enabled) {
                    $event->reminders()->create([
                        'channel' => 'database',
                        'minutes_before' => 15,
                        'remind_at' => $start->copy()->subMinutes(15),
                    ]);
                }
            }
        }

        LandingPage::query()
            ->where('slug', 'water-awareness-show')
            ->update(['conversion_count' => 12]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, FunnelStage>  $openStages
     * @param  \Illuminate\Support\Collection<int, LeadSource>  $sources
     * @param  \Illuminate\Support\Collection<int, Tag>  $tags
     * @return \Illuminate\Support\Collection<int, Lead|Prospect|Customer>
     */
    private function seedLeadsForAgent(
        User $agent,
        ?Team $team,
        ?Funnel $funnel,
        $openStages,
        ?FunnelStage $wonStage,
        ?FunnelStage $lostStage,
        $sources,
        $tags,
        int $agentIndex,
    ) {
        $created = collect();

        $prospects = Prospect::factory()
            ->count(3)
            ->assignedTo($agent)
            ->fromSource($sources->random())
            ->when($funnel && $openStages->isNotEmpty(), fn ($factory) => $factory->inStage($openStages->get($agentIndex % $openStages->count())))
            ->create([
                'team_id' => $team?->id,
                'temperature' => LeadTemperature::Hot,
                'next_follow_up_at' => now(),
                'created_at' => now()->subDays(rand(1, 6)),
            ]);
        $created = $created->merge($prospects);

        $leads = Lead::factory()
            ->count(4)
            ->assignedTo($agent)
            ->fromSource($sources->random())
            ->when($funnel && $openStages->isNotEmpty(), fn ($factory) => $factory->inStage($openStages->random()))
            ->create([
                'team_id' => $team?->id,
                'next_follow_up_at' => now()->addDays(rand(1, 4)),
                'created_at' => now()->subDays(rand(3, 20)),
            ]);
        $created = $created->merge($leads);

        $clients = Customer::factory()
            ->count(2)
            ->assignedTo($agent)
            ->fromSource($sources->random())
            ->when($wonStage, fn ($factory) => $factory->inStage($wonStage))
            ->create([
                'team_id' => $team?->id,
                'status' => 'customer',
                'converted_at' => now()->subDays(rand(5, 30)),
                'updated_at' => now()->subDays(rand(1, 10)),
            ]);
        $created = $created->merge($clients);

        if ($lostStage) {
            $lost = Lead::factory()
                ->assignedTo($agent)
                ->fromSource($sources->random())
                ->inStage($lostStage)
                ->create([
                    'team_id' => $team?->id,
                    'status' => 'inactive',
                    'lost_reason' => 'Not ready to purchase',
                ]);
            $created->push($lost);
        }

        $created->each(function (Lead|Prospect|Customer $lead) use ($tags) {
            if ($tags->isNotEmpty() && rand(0, 1)) {
                $lead->tags()->sync($tags->random(rand(1, 2))->pluck('id'));
            }
        });

        return $created;
    }
}
