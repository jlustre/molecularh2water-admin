<?php

namespace Database\Factories;

use App\Enums\Crm\TaskPriority;
use App\Enums\Crm\TaskStatus;
use App\Models\Crm\Lead;
use App\Models\Crm\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'priority' => TaskPriority::Normal,
            'status' => TaskStatus::Pending,
            'due_at' => now()->addDays(fake()->numberBetween(1, 7)),
            'reminder_at' => null,
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

    public function withReminder(): static
    {
        return $this->state(fn () => ['reminder_at' => now()->subMinute()]);
    }
}
