<?php

namespace Database\Factories;

use App\Enums\DirectoryCustomerStatus;
use App\Models\DirectoryCustomer;
use App\Support\UsStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DirectoryCustomer>
 */
class DirectoryCustomerFactory extends Factory
{
    protected $model = DirectoryCustomer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('(###) ###-####'),
            'street_address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->randomElement(UsStates::abbreviations()),
            'postal_code' => fake()->postcode(),
            'status' => DirectoryCustomerStatus::Active,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => DirectoryCustomerStatus::Archived,
        ]);
    }
}
