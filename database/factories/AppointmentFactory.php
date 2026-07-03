<?php

namespace Database\Factories;

use App\Enums\Crm\AppointmentStatus;
use App\Models\Crm\Appointment;
use App\Models\Crm\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $starts = now()->addDays(fake()->numberBetween(1, 14))->setHour(10)->setMinute(0);

        return [
            'title' => fake()->sentence(3),
            'meeting_type' => 'home_demo',
            'location' => fake()->optional()->address(),
            'zoom_link' => fake()->optional()->url(),
            'status' => AppointmentStatus::Scheduled,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'reminder_notes' => null,
        ];
    }

    public function forLead(Lead|\App\Models\Crm\Prospect|\App\Models\Crm\Customer|\App\Models\Crm\Recruit $lead): static
    {
        return $this->state(fn () => [
            'contact_type' => $lead->getMorphClass(),
            'contact_id' => $lead->id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function startingSoon(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->addMinutes(30),
            'ends_at' => now()->addMinutes(90),
        ]);
    }
}
