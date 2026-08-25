<?php

namespace Database\Factories;

use App\Enums\IssueReportCategory;
use App\Enums\IssueReportSeverity;
use App\Enums\IssueReportSite;
use App\Enums\IssueReportSource;
use App\Enums\IssueReportStatus;
use App\Models\IssueReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IssueReport>
 */
class IssueReportFactory extends Factory
{
    protected $model = IssueReport::class;

    public function definition(): array
    {
        return [
            'reporter_name' => fake()->name(),
            'reporter_email' => fake()->safeEmail(),
            'reporter_phone' => fake()->optional()->numerify('555-####'),
            'source' => IssueReportSource::PublicWebsite,
            'site' => fake()->randomElement(IssueReportSite::cases()),
            'category' => fake()->randomElement(IssueReportCategory::cases()),
            'severity' => fake()->randomElement(IssueReportSeverity::cases()),
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'page_url' => fake()->optional()->url(),
            'status' => IssueReportStatus::New,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (): array => [
            'status' => IssueReportStatus::New,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => [
            'status' => IssueReportStatus::Resolved,
            'resolution_summary' => 'The reported issue has been fixed.',
            'resolved_at' => now(),
        ]);
    }
}
