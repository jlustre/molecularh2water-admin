<?php

namespace Database\Factories;

use App\Enums\Crm\CalendarEventPriority;
use App\Enums\Crm\CalendarEventStatus;
use App\Models\Crm\CalendarEvent;
use App\Models\Crm\CalendarEventType;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarEvent>
 */
class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(1, 14))->setHour(fake()->numberBetween(9, 16))->setMinute(0);

        return [
            'business_line' => 'h2s',
            'calendar_event_type_id' => CalendarEventType::query()->value('id') ?? 1,
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'start_at' => $start,
            'end_at' => $start->copy()->addHour(),
            'timezone' => config('calendar.default_timezone', 'UTC'),
            'status' => CalendarEventStatus::Scheduled,
            'priority' => CalendarEventPriority::Normal,
            'reminder_enabled' => true,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function forLead(Lead|Prospect|Customer|Recruit $lead): static
    {
        return $this->state(fn () => [
            'related_type' => $lead->getMorphClass(),
            'related_id' => $lead->id,
            'team_id' => $lead->team_id,
        ]);
    }

    public function startingSoon(): static
    {
        return $this->state(fn () => [
            'start_at' => now()->addMinutes(30),
            'end_at' => now()->addMinutes(90),
        ]);
    }
}
