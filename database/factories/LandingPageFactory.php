<?php

namespace Database\Factories;

use App\Models\Crm\Funnel;
use App\Models\Crm\LandingPage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LandingPage>
 */
class LandingPageFactory extends Factory
{
    protected $model = LandingPage::class;

    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'funnel_id' => Funnel::query()->where('is_default', true)->value('id'),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 999),
            'headline' => fake()->sentence(),
            'subheadline' => fake()->sentence(),
            'tracking_source' => 'Landing Page',
            'conversion_count' => 0,
            'is_published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['is_published' => true]);
    }
}
