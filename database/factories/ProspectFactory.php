<?php

namespace Database\Factories;

use App\Enums\Crm\LeadStatus;
use App\Enums\Crm\LeadTemperature;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\LeadSource;
use App\Models\Crm\Lifecycle;
use App\Models\Crm\Prospect;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prospect>
 */
class ProspectFactory extends Factory
{
    protected $model = Prospect::class;

    public function definition(): array
    {
        return [
            'lifecycle_id' => Lifecycle::idFor('prospect'),
            'business_line' => 'h2s',
            'status' => LeadStatus::New,
            'temperature' => LeadTemperature::Cold,
            'score' => fake()->numberBetween(0, 100),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'country' => 'US',
            'consent_given' => true,
        ];
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn () => ['assigned_user_id' => $user->id]);
    }

    public function hot(): static
    {
        return $this->state(fn () => ['temperature' => LeadTemperature::Hot]);
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
