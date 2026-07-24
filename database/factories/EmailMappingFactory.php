<?php

namespace Database\Factories;

use App\Enums\NotifiableForm;
use App\Models\EmailMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailMapping>
 */
class EmailMappingFactory extends Factory
{
    protected $model = EmailMapping::class;

    public function definition(): array
    {
        return [
            'form_key' => fake()->randomElement(NotifiableForm::cases())->value,
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function forForm(NotifiableForm $form): static
    {
        return $this->state(fn () => ['form_key' => $form->value]);
    }
}
