<?php

namespace App\Livewire\Crm;

use App\Livewire\Crm\Concerns\UsesCrmLayout;
use App\Models\Crm\Funnel;
use App\Models\Crm\FunnelStage;
use App\Services\Crm\FunnelSeederExporter;
use App\Services\Crm\FunnelService;
use App\Support\Crm\PipelineContacts;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Component;

class FunnelManager extends Component
{
    use UsesCrmLayout;

    public ?int $funnelId = null;

    public string $newStageName = '';

    public string $newStageColor = 'slate';

    public bool $newStageIsWon = false;

    public bool $newStageIsLost = false;

    public string $newFunnelName = '';

    public string $newFunnelDescription = '';

    public ?int $editingStageId = null;

    public string $editName = '';

    public string $editColor = 'slate';

    public bool $editIsWon = false;

    public bool $editIsLost = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('funnel.manage'), 403);

        if (! Schema::hasTable('funnels')) {
            return;
        }

        $this->funnelId = Funnel::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->value('id');
    }

    public function updatedFunnelId(): void
    {
        $this->cancelEdit();
    }

    public function addStage(FunnelService $funnelService): void
    {
        $funnel = $this->funnel();

        if (! $funnel) {
            return;
        }

        $validated = $this->validate([
            'newStageName' => ['required', 'string', 'max:120'],
            'newStageColor' => ['required', Rule::in(array_keys(config('crm.stage_colors', [])))],
            'newStageIsWon' => ['boolean'],
            'newStageIsLost' => ['boolean'],
        ]);

        $funnelService->createStage($funnel, [
            'name' => $validated['newStageName'],
            'color' => $validated['newStageColor'],
            'is_won' => $validated['newStageIsWon'],
            'is_lost' => $validated['newStageIsLost'],
        ]);

        $this->reset(['newStageName', 'newStageColor', 'newStageIsWon', 'newStageIsLost']);
        $this->newStageColor = 'slate';

        session()->flash('status', 'Stage added.');
    }

    public function createFunnel(FunnelService $funnelService): void
    {
        $validated = $this->validate([
            'newFunnelName' => ['required', 'string', 'max:120'],
            'newFunnelDescription' => ['nullable', 'string', 'max:500'],
        ]);

        $funnel = $funnelService->createFunnel([
            'name' => $validated['newFunnelName'],
            'description' => $validated['newFunnelDescription'] ?: null,
        ], [
            ['name' => 'New', 'slug' => 'new', 'color' => 'slate', 'sort_order' => 1],
            ['name' => 'In Progress', 'slug' => 'in-progress', 'color' => 'cyan', 'sort_order' => 2],
            ['name' => 'Closed Won', 'slug' => 'closed-won', 'color' => 'emerald', 'sort_order' => 3, 'is_won' => true],
            ['name' => 'Closed Lost', 'slug' => 'closed-lost', 'color' => 'rose', 'sort_order' => 4, 'is_lost' => true],
        ]);

        $this->funnelId = $funnel->id;
        $this->reset(['newFunnelName', 'newFunnelDescription']);
        $this->cancelEdit();
        session()->flash('status', 'Pipeline created.');
    }

    public function selectFunnel(int $funnelId): void
    {
        abort_unless(
            Funnel::query()->where('is_active', true)->whereKey($funnelId)->exists(),
            404,
        );

        $this->funnelId = $funnelId;
        $this->cancelEdit();
        $this->resetErrorBag('funnel');
    }

    public function deleteFunnel(int $funnelId, FunnelService $funnelService): void
    {
        $funnel = Funnel::query()->where('is_active', true)->whereKey($funnelId)->firstOrFail();

        try {
            $funnelService->deleteFunnel($funnel);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->addError('funnel', $exception->errors()['funnel'][0] ?? 'Unable to delete pipeline.');

            return;
        }

        if ($this->funnelId === $funnelId) {
            $this->funnelId = Funnel::query()
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->value('id');
            $this->cancelEdit();
        }

        session()->flash('status', 'Pipeline deleted.');
    }

    public function startEdit(int $stageId): void
    {
        $stage = $this->stageForFunnel($stageId);

        $this->editingStageId = $stage->id;
        $this->editName = $stage->name;
        $this->editColor = $stage->color ?? 'slate';
        $this->editIsWon = $stage->is_won;
        $this->editIsLost = $stage->is_lost;
    }

    public function saveEdit(FunnelService $funnelService): void
    {
        $stage = $this->stageForFunnel((int) $this->editingStageId);

        $validated = $this->validate([
            'editName' => ['required', 'string', 'max:120'],
            'editColor' => ['required', Rule::in(array_keys(config('crm.stage_colors', [])))],
            'editIsWon' => ['boolean'],
            'editIsLost' => ['boolean'],
        ]);

        $funnelService->updateStage($stage, [
            'name' => $validated['editName'],
            'color' => $validated['editColor'],
            'is_won' => $validated['editIsWon'],
            'is_lost' => $validated['editIsLost'],
        ]);

        $this->cancelEdit();
        session()->flash('status', 'Stage updated.');
    }

    public function deleteStage(int $stageId, FunnelService $funnelService): void
    {
        $stage = $this->stageForFunnel($stageId);

        try {
            $funnelService->deleteStage($stage);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->addError('stage', $exception->errors()['stage'][0] ?? 'Unable to delete stage.');

            return;
        }

        if ($this->editingStageId === $stageId) {
            $this->cancelEdit();
        }

        session()->flash('status', 'Stage deleted.');
    }

    public function moveStage(int $stageId, string $direction, FunnelService $funnelService): void
    {
        $stage = $this->stageForFunnel($stageId);
        $funnelService->moveStage($stage, $direction);
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingStageId', 'editName', 'editColor', 'editIsWon', 'editIsLost']);
        $this->editColor = 'slate';
    }

    public function updateSeeder(FunnelSeederExporter $exporter): void
    {
        abort_unless($this->canUpdateSeeder(), 403);

        $result = $exporter->export();

        session()->flash(
            'status',
            'FunnelsSeeder.php updated with '.$result['funnel_count'].' funnel'
                .($result['funnel_count'] === 1 ? '' : 's')
                .' and '.$result['stage_count'].' stage'
                .($result['stage_count'] === 1 ? '' : 's').'.',
        );
    }

    public function canUpdateSeeder(): bool
    {
        return (bool) auth()->user()?->isSuperAdmin();
    }

    public function render()
    {
        $funnels = Schema::hasTable('funnels')
            ? Funnel::query()
                ->where('is_active', true)
                ->withCount('stages')
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get()
            : collect();

        $funnels->each(function (Funnel $funnel) {
            $funnel->contacts_count = PipelineContacts::countForFunnel($funnel->id);
        });

        if ($this->funnelId && ! $funnels->contains('id', $this->funnelId)) {
            $this->funnelId = $funnels->first()?->id;
        }

        $selectedFunnel = $this->funnel();

        $stages = $selectedFunnel
            ? $selectedFunnel->stages()->orderBy('sort_order')->get()
            : collect();

        $stages->each(function (FunnelStage $stage) {
            $stage->leads_count = PipelineContacts::countForStage($stage->id);
        });

        return view('livewire.crm.funnel-manager', [
            'funnels' => $funnels,
            'selectedFunnel' => $selectedFunnel,
            'stages' => $stages,
            'stageColors' => config('crm.stage_colors', []),
        ])->layout($this->crmLayout());
    }

    private function funnel(): ?Funnel
    {
        if (! $this->funnelId) {
            return null;
        }

        return Funnel::query()->find($this->funnelId);
    }

    private function stageForFunnel(int $stageId): FunnelStage
    {
        $funnel = $this->funnel();

        abort_unless($funnel, 404);

        return $funnel->stages()->whereKey($stageId)->firstOrFail();
    }
}
