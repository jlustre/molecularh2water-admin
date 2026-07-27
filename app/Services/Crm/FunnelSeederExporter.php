<?php

namespace App\Services\Crm;

use App\Models\Crm\Funnel;
use Illuminate\Support\Facades\File;

class FunnelSeederExporter
{
    /**
     * Write current funnels and stages into FunnelsSeeder.php.
     *
     * @return array{path: string, funnel_count: int, stage_count: int}
     */
    public function export(): array
    {
        $funnels = Funnel::query()
            ->with(['stages' => fn ($query) => $query->orderBy('sort_order')])
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->map(fn (Funnel $funnel) => [
                'slug' => $funnel->slug,
                'name' => $funnel->name,
                'description' => $funnel->description,
                'is_default' => (bool) $funnel->is_default,
                'is_active' => (bool) $funnel->is_active,
                'stages' => $funnel->stages
                    ->map(fn ($stage) => array_filter([
                        'name' => $stage->name,
                        'slug' => $stage->slug,
                        'color' => $stage->color ?: 'slate',
                        'sort_order' => (int) $stage->sort_order,
                        'is_won' => $stage->is_won ? true : null,
                        'is_lost' => $stage->is_lost ? true : null,
                    ], fn ($value) => $value !== null))
                    ->values()
                    ->all(),
            ])
            ->all();

        $exported = var_export($funnels, true);
        $generatedAt = now()->toDateTimeString();
        $path = database_path('seeders/FunnelsSeeder.php');

        File::put($path, <<<PHP
<?php

namespace Database\\Seeders;

use App\\Models\\Crm\\Funnel;
use App\\Services\\Crm\\FunnelService;
use Illuminate\\Database\\Seeder;

class FunnelsSeeder extends Seeder
{
    /**
     * Seed CRM funnels and stages from the admin export generated at {$generatedAt}.
     */
    public function run(): void
    {
        \$funnels = {$exported};

        \$funnelService = app(FunnelService::class);

        foreach (\$funnels as \$funnelData) {
            \$funnel = Funnel::query()->updateOrCreate(
                ['slug' => \$funnelData['slug']],
                [
                    'name' => \$funnelData['name'],
                    'description' => \$funnelData['description'] ?? null,
                    'is_default' => (bool) (\$funnelData['is_default'] ?? false),
                    'is_active' => (bool) (\$funnelData['is_active'] ?? true),
                ],
            );

            \$funnelService->seedStages(\$funnel, \$funnelData['stages'] ?? []);
        }
    }
}
PHP);

        return [
            'path' => $path,
            'funnel_count' => count($funnels),
            'stage_count' => collect($funnels)->sum(fn (array $funnel) => count($funnel['stages'] ?? [])),
        ];
    }
}
