<?php

namespace Database\Factories;

use App\Enums\WebsiteFormSubmissionStatus;
use App\Enums\WebsiteFormType;
use App\Models\WebsiteFormSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebsiteFormSubmission>
 */
class WebsiteFormSubmissionFactory extends Factory
{
    protected $model = WebsiteFormSubmission::class;

    public function definition(): array
    {
        $type = fake()->randomElement(WebsiteFormType::cases());

        return [
            'form_type' => $type,
            'status' => WebsiteFormSubmissionStatus::New,
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('555-####'),
            'referrer_name' => fake()->optional()->name(),
            'preferred_time' => fake()->optional()->sentence(3),
            'interested_in' => fake()->sentence(4),
            'message' => fake()->optional()->paragraph(),
            'source' => 'website',
            'form_context' => $type->formContext(),
            'tracking_source' => $type->formContext(),
            'page_url' => 'https://www.molecularh2water.com'.$type->frontendPath(),
            'consent_given' => true,
            'admin_notes' => null,
            'prospect_id' => null,
        ];
    }

    public function ofType(WebsiteFormType $type): static
    {
        return $this->state(fn (): array => [
            'form_type' => $type,
            'form_context' => $type->formContext(),
            'tracking_source' => $type->formContext(),
            'page_url' => 'https://www.molecularh2water.com'.$type->frontendPath(),
        ]);
    }
}
