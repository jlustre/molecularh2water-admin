<?php

namespace Database\Seeders;

use App\Enums\BusinessLine;
use App\Enums\Crm\CalendarEventStatus;
use App\Enums\Crm\LeadLifecycle;
use App\Enums\Crm\TaskStatus;
use App\Models\Crm\CalendarEvent;
use App\Models\Crm\CalendarEventType;
use App\Models\Crm\Lifecycle;
use App\Models\Crm\Prospect;
use App\Models\Crm\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class CalendarWidgetsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CalendarSeeder::class);

        $user = User::query()
            ->where('email', 'jclustre@gmail.com')
            ->orWhere('name', 'like', '%Lustre%')
            ->first();

        if (! $user) {
            $this->command?->warn('No Joey/Joane Lustre user found. Skipping CalendarWidgetsDemoSeeder.');

            return;
        }

        // Prefer Joey Lustre (jclustre@gmail.com); also matches a Joane Lustre account if present.
        $this->command?->info("Seeding calendar widget demo data for {$user->name} ({$user->email}).");

        $businessLine = \App\Enums\BusinessLine::Both->value;

        $prospect = Prospect::query()->updateOrCreate(
            ['email' => 'calendar.widget.demo@example.com'],
            [
                'lifecycle_id' => Lifecycle::idFor(LeadLifecycle::Prospect),
                'business_line' => $businessLine,
                'status' => 'new',
                'temperature' => 'warm',
                'score' => 40,
                'first_name' => 'Widget',
                'last_name' => 'Demo',
                'phone' => '555-0147',
                'assigned_user_id' => $user->id,
                'consent_given' => true,
                'interested_in' => 'Water Awareness Show',
                'message' => 'Seeded for calendar widget testing.',
                'next_follow_up_at' => now()->subDays(2)->setTime(9, 0),
            ],
        );

        $showType = CalendarEventType::query()->where('slug', 'water-awareness-show')->first()
            ?? CalendarEventType::query()->where('category', 'show')->first();
        $demoType = CalendarEventType::query()->where('slug', 'home-demo')->first()
            ?? CalendarEventType::query()->where('category', 'demo')->first();
        $callType = CalendarEventType::query()->where('slug', 'phone-call')->first();
        $followUpType = CalendarEventType::query()->where('slug', 'follow-up')->first()
            ?? $callType;

        if ($showType) {
            $start = now()->addDays(2)->setTime(18, 30);
            CalendarEvent::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'title' => 'Demo: Water Awareness Show (Joey)',
                    'calendar_event_type_id' => $showType->id,
                ],
                [
                    'business_line' => $businessLine,
                    'related_type' => $prospect->getMorphClass(),
                    'related_id' => $prospect->id,
                    'description' => 'Seeded upcoming show for calendar widgets.',
                    'start_at' => $start,
                    'end_at' => $start->copy()->addHours(2),
                    'timezone' => config('calendar.default_timezone', config('app.timezone')),
                    'location' => 'Community Center Room B',
                    'status' => CalendarEventStatus::Scheduled,
                    'priority' => 'normal',
                    'reminder_enabled' => true,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ],
            );
        }

        if ($demoType) {
            $start = now()->addDays(5)->setTime(14, 0);
            CalendarEvent::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'title' => 'Demo: Home Product Demo (Joey)',
                    'calendar_event_type_id' => $demoType->id,
                ],
                [
                    'business_line' => $businessLine,
                    'description' => 'Seeded upcoming home demo.',
                    'start_at' => $start,
                    'end_at' => $start->copy()->addHour(),
                    'timezone' => config('calendar.default_timezone', config('app.timezone')),
                    'location' => '123 Demo Lane',
                    'status' => CalendarEventStatus::Scheduled,
                    'priority' => 'normal',
                    'reminder_enabled' => true,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ],
            );
        }

        if ($callType) {
            $start = now()->addHours(2)->minute(0)->second(0);
            if (! $start->isToday()) {
                $start = now()->endOfDay()->subHours(2);
            }

            CalendarEvent::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'title' => 'Phone call with Widget Demo',
                    'calendar_event_type_id' => $callType->id,
                ],
                [
                    'business_line' => $businessLine,
                    'related_type' => $prospect->getMorphClass(),
                    'related_id' => $prospect->id,
                    'description' => 'General follow-up · Phone: 555-0147',
                    'start_at' => $start,
                    'end_at' => $start->copy()->addMinutes(15),
                    'timezone' => config('calendar.default_timezone', config('app.timezone')),
                    'status' => CalendarEventStatus::Scheduled,
                    'priority' => 'normal',
                    'reminder_enabled' => true,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'metadata' => [
                        'phone_call_reason' => 'general_follow_up',
                        'phone_number' => '555-0147',
                        'contact_kind' => 'prospect',
                    ],
                ],
            );
        }

        if ($followUpType) {
            $start = now()->subDays(1)->setTime(10, 30);
            CalendarEvent::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'title' => 'Phone call with Overdue Widget Contact',
                    'calendar_event_type_id' => $followUpType->id,
                ],
                [
                    'business_line' => $businessLine,
                    'description' => 'Missed follow-up · Phone: 555-0199',
                    'start_at' => $start,
                    'end_at' => $start->copy()->addMinutes(15),
                    'timezone' => config('calendar.default_timezone', config('app.timezone')),
                    'status' => CalendarEventStatus::Scheduled,
                    'priority' => 'high',
                    'reminder_enabled' => true,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'metadata' => [
                        'phone_call_reason' => 'general_follow_up',
                        'phone_number' => '555-0199',
                        'contact_kind' => 'other',
                        'other_contact_name' => 'Overdue Widget Contact',
                    ],
                ],
            );
        }

        Task::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'title' => 'Prep Water Awareness Show materials',
            ],
            [
                'business_line' => $businessLine,
                'contact_type' => $prospect->getMorphClass(),
                'contact_id' => $prospect->id,
                'description' => 'Seeded task due today for calendar widgets.',
                'priority' => 'high',
                'status' => TaskStatus::Pending,
                'due_at' => now()->setTime(16, 0),
            ],
        );

        Task::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'title' => 'Confirm demo guest list',
            ],
            [
                'business_line' => $businessLine,
                'description' => 'Second seeded task for today.',
                'priority' => 'normal',
                'status' => TaskStatus::InProgress,
                'due_at' => now()->setTime(11, 0),
            ],
        );

        $this->command?->info('Calendar widget demo records ready. Open /crm/calendar while logged in as Joey Lustre.');
    }
}
