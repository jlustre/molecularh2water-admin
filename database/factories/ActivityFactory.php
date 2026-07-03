<?php

namespace Database\Factories;

use App\Models\Crm\Activity;
use App\Models\Crm\ActivityType;
use App\Models\Crm\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return [
            'activity_type_id' => ActivityType::query()->value('id') ?? 1,
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'outcome' => 'connected',
            'completed_at' => now(),
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
}
