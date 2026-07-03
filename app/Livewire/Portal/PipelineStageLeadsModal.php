<?php

namespace App\Livewire\Portal;

use App\Models\Crm\FunnelStage;
use App\Services\Crm\DashboardStatsService;
use Livewire\Attributes\On;
use Livewire\Component;

class PipelineStageLeadsModal extends Component
{
    public bool $show = false;

    public ?int $stageId = null;

    public string $stageName = '';

    #[On('open-pipeline-stage-leads')]
    public function open(int $stageId): void
    {
        $user = auth()->user();

        abort_unless(
            $user?->hasPermission('pipeline.view') && $user->hasPermission('leads.view'),
            403,
        );

        $stage = FunnelStage::query()->find($stageId);

        if (! $stage) {
            return;
        }

        $this->stageId = $stage->id;
        $this->stageName = $stage->name;
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->stageId = null;
        $this->stageName = '';
    }

    public function render(DashboardStatsService $stats)
    {
        $leads = $this->show && $this->stageId
            ? $stats->leadsInStage($this->stageId)
            : collect();

        return view('livewire.portal.pipeline-stage-leads-modal', [
            'leads' => $leads,
        ]);
    }
}
