<?php

namespace Database\Factories;

use App\Enums\InstallerStatus;
use App\Models\Installer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Installer>
 */
class InstallerFactory extends Factory
{
    protected $model = Installer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('(###) ###-####'),
            'company' => fake()->optional()->company(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'status' => InstallerStatus::Active,
            'notes' => fake()->optional()->sentence(),
            'archived_at' => null,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => InstallerStatus::Archived,
            'archived_at' => now(),
        ]);
    }
}
