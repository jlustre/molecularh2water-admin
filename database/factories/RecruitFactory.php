<?php

namespace Database\Factories;

use App\Enums\Crm\EngagementType;
use App\Enums\Crm\LeadStatus;
use App\Enums\Crm\LeadTemperature;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\LeadSource;
use App\Models\Crm\Lifecycle;
use App\Models\Crm\Recruit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recruit>
 */
class RecruitFactory extends Factory
{
    protected $model = Recruit::class;

    public function definition(): array
    {
        return [
            'lifecycle_id' => Lifecycle::idFor('recruit'),
            'business_line' => 'h2s',
            'status' => LeadStatus::New,
            'engagement_type' => EngagementType::Recruit,
            'temperature' => LeadTemperature::Cold,
            'score' => fake()->numberBetween(0, 100),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'consent_given' => true,
        ];
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn () => ['assigned_user_id' => $user->id]);
    }

    public function inStage(FunnelStage $stage): static
    {
        return $this->state(fn () => [
            'funnel_id' => $stage->funnel_id,
            'funnel_stage_id' => $stage->id,
        ]);
    }

    public function fromSource(LeadSource $source): static
    {
        return $this->state(fn () => ['lead_source_id' => $source->id]);
    }
}
