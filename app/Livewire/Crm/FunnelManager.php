<?php

namespace App\Livewire\Crm;

use App\Livewire\Crm\Concerns\UsesCrmLayout;
use App\Models\Crm\Funnel;
use App\Models\Crm\FunnelStage;
use App\Services\Crm\FunnelService;
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
        session()->flash('status', 'Pipeline created.');
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

    public function render()
    {
        $funnels = Schema::hasTable('funnels')
            ? Funnel::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get()
            : collect();

        $stages = $this->funnel()
            ? $this->funnel()->stages()->withCount('leads')->get()
            : collect();

        return view('livewire.crm.funnel-manager', [
            'funnels' => $funnels,
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
