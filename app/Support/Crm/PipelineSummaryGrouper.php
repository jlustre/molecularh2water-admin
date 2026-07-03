<?php

namespace App\Support\Crm;

use App\Models\Crm\FunnelStage;
use Illuminate\Support\Collection;

class PipelineSummaryGrouper
{
    /**
     * @param  Collection<int, FunnelStage>  $stages
     * @return list<array{label: string, stages: Collection<int, FunnelStage>}>
     */
    public function group(Collection $stages): array
    {
        if ($stages->isEmpty()) {
            return [];
        }

        $bySlug = $stages->keyBy('slug');
        $assignedSlugs = [];
        $groups = [];

        foreach (config('crm.pipeline_summary_groups', []) as $definition) {
            $groupStages = collect($definition['slugs'] ?? [])
                ->map(fn (string $slug) => $bySlug->get($slug))
                ->filter()
                ->values();

            foreach ($groupStages as $stage) {
                $assignedSlugs[$stage->slug] = true;
            }

            if ($groupStages->isNotEmpty()) {
                $groups[] = [
                    'label' => $definition['label'],
                    'stages' => $groupStages,
                ];
            }
        }

        $remaining = $stages
            ->filter(fn (FunnelStage $stage) => ! isset($assignedSlugs[$stage->slug]))
            ->values();

        if ($remaining->isNotEmpty()) {
            $groups[] = [
                'label' => 'Other',
                'stages' => $remaining,
            ];
        }

        return $groups;
    }
}
