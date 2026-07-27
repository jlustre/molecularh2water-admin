<?php

namespace Database\Factories;

use App\Enums\InstallerInstallationStatus;
use App\Models\Installer;
use App\Models\InstallerInstallation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstallerInstallation>
 */
class InstallerInstallationFactory extends Factory
{
    protected $model = InstallerInstallation::class;

    public function definition(): array
    {
        return [
            'installer_id' => Installer::factory(),
            'status' => InstallerInstallationStatus::Scheduled,
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->numerify('(###) ###-####'),
            'street_address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'postal_code' => fake()->postcode(),
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+30 days'),
            'completed_at' => null,
            'cancelled_at' => null,
            'rescheduled_at' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => InstallerInstallationStatus::Completed,
            'scheduled_at' => now()->subDays(3),
            'completed_at' => now()->subDay(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => InstallerInstallationStatus::Cancelled,
            'cancelled_at' => now()->subDay(),
        ]);
    }

    public function rescheduled(): static
    {
        return $this->state(fn () => [
            'status' => InstallerInstallationStatus::Rescheduled,
            'rescheduled_at' => now(),
            'scheduled_at' => now()->addDays(7),
        ]);
    }
}
