<?php

namespace App\Livewire\Crm;

use App\Livewire\Crm\Concerns\UsesCrmLayout;
use App\Models\Crm\Funnel;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\LostReason;
use App\Services\Crm\CalendarEventService;
use App\Services\Crm\FunnelService;
use App\Support\Crm\CrmRoutes;
use App\Support\Crm\PipelineContacts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class FunnelBoard extends Component
{
    use AuthorizesRequests;
    use UsesCrmLayout;

    public ?int $funnelId = null;

    public string $lifecycleFilter = '';

    public bool $showLostModal = false;

    public ?string $pendingContactType = null;

    public ?int $pendingLeadId = null;

    public ?int $pendingStageId = null;

    public ?int $lostReasonId = null;

    public string $lostReasonDetail = '';

    public bool $showCalendarSuggestion = false;

    public ?string $suggestionContactType = null;

    public ?int $suggestionLeadId = null;

    public ?string $suggestionTitle = null;

    public ?string $suggestionEventType = null;

    public function mount(): void
    {
        $this->resolveFunnelId();
    }

    public function updatedFunnelId(): void
    {
        // Board stages are loaded fresh in render().
    }

    public function updatedLifecycleFilter(): void
    {
        // Board stages are loaded fresh in render().
    }

    public function requestMoveLead(string $contactType, int $contactId, int $stageId): void
    {
        abort_unless(auth()->user()?->hasPermission('pipeline.manage'), 403);

        $lead = PipelineContacts::findAccessible($contactType, $contactId);
        $this->authorize('moveOnPipeline', $lead);

        $stage = FunnelStage::query()->findOrFail($stageId);

        if ($stage->is_lost) {
            $this->pendingContactType = $contactType;
            $this->pendingLeadId = $contactId;
            $this->pendingStageId = $stageId;
            $this->lostReasonId = null;
            $this->lostReasonDetail = '';
            $this->showLostModal = true;

            return;
        }

        $this->moveLead($contactType, $contactId, $stageId);
    }

    public function confirmLostMove(FunnelService $funnelService): void
    {
        $this->validate([
            'lostReasonId' => ['required', 'exists:lost_reasons,id'],
            'lostReasonDetail' => [
                'nullable',
                'string',
                'max:500',
                \Illuminate\Validation\Rule::requiredIf(
                    fn () => LostReason::query()->find($this->lostReasonId)?->requires_detail ?? false,
                ),
            ],
        ]);

        $this->moveLead(
            (string) $this->pendingContactType,
            (int) $this->pendingLeadId,
            (int) $this->pendingStageId,
            $this->lostReasonId,
            $this->lostReasonDetail,
            $funnelService,
        );
        $this->resetLostModal();
    }

    public function cancelLostMove(): void
    {
        $this->resetLostModal();
    }

    public function moveLead(
        string $contactType,
        int $contactId,
        int $stageId,
        ?int $lostReasonId = null,
        ?string $lostReasonDetail = null,
        ?FunnelService $funnelService = null,
    ): void {
        abort_unless(auth()->user()?->hasPermission('pipeline.manage'), 403);

        $lead = PipelineContacts::findAccessible($contactType, $contactId);
        $this->authorize('moveOnPipeline', $lead);

        $stage = FunnelStage::query()->findOrFail($stageId);

        $moved = ($funnelService ?? app(FunnelService::class))->moveLead(
            $lead,
            $stage,
            auth()->user(),
            $lostReasonId,
            $lostReasonDetail,
        );

        $suggestion = app(CalendarEventService::class)->suggestForStage($stage);

        if ($suggestion && auth()->user()?->hasPermission('calendar.view')) {
            $this->suggestionContactType = $moved->getMorphClass();
            $this->suggestionLeadId = $moved->id;
            $this->suggestionTitle = $suggestion['title'];
            $this->suggestionEventType = $suggestion['event_type_slug'];
            $this->showCalendarSuggestion = true;
        }
    }

    public function dismissCalendarSuggestion(): void
    {
        $this->reset([
            'showCalendarSuggestion',
            'suggestionContactType',
            'suggestionLeadId',
            'suggestionTitle',
            'suggestionEventType',
        ]);
    }

    public function calendarSuggestionUrl(): ?string
    {
        if (! $this->suggestionLeadId || ! $this->suggestionContactType) {
            return null;
        }

        return route(\App\Support\Crm\CrmRoutes::name('calendar.index'), [
            'lead' => $this->suggestionLeadId,
        ]);
    }

    public function leadProfileUrl(Model $lead): string
    {
        return match ($lead->getMorphClass()) {
            'prospect' => CrmRoutes::url('prospects.show', ['lead' => $lead]),
            'customer' => CrmRoutes::url('customers.show', ['lead' => $lead]),
            'recruit' => CrmRoutes::url('recruits.show', ['lead' => $lead]),
            default => CrmRoutes::url('leads.show', ['lead' => $lead]),
        };
    }

    public function render()
    {
        $funnels = Schema::hasTable('funnels')
            ? Funnel::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get()
            : collect();

        return view('livewire.crm.funnel-board', [
            'funnels' => $funnels,
            'stages' => $this->boardStages(),
            'lostReasons' => LostReason::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ])->layout($this->crmLayout());
    }

    /**
     * @return Collection<int, FunnelStage>
     */
    private function boardStages(): Collection
    {
        if (! Schema::hasTable('funnels')) {
            return collect();
        }

        $this->resolveFunnelId();

        if (! $this->funnelId) {
            return collect();
        }

        $funnel = Funnel::query()->find($this->funnelId);

        if (! $funnel) {
            return collect();
        }

        $stages = $funnel->stages()->orderBy('sort_order')->get();
        $contactsByStage = PipelineContacts::forStages(
            $stages->pluck('id'),
            auth()->user(),
            $this->lifecycleFilter !== '' ? $this->lifecycleFilter : null,
        );

        $stages->each(function (FunnelStage $stage) use ($contactsByStage) {
            $stage->setRelation('leads', $contactsByStage->get($stage->id, collect()));
        });

        return $stages;
    }

    private function resolveFunnelId(): void
    {
        if (! Schema::hasTable('funnels')) {
            return;
        }

        if ($this->funnelId && Funnel::query()->whereKey($this->funnelId)->exists()) {
            return;
        }

        $this->funnelId = Funnel::query()
            ->where('is_default', true)
            ->where('is_active', true)
            ->value('id');
    }

    private function resetLostModal(): void
    {
        $this->reset([
            'showLostModal',
            'pendingContactType',
            'pendingLeadId',
            'pendingStageId',
            'lostReasonId',
            'lostReasonDetail',
        ]);
    }
}
