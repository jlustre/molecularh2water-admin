<?php

namespace App\Livewire\Crm;

use App\Enums\Crm\LeadLifecycle;
use App\Livewire\Crm\Concerns\UsesCrmLayout;
use App\Models\Crm\Funnel;
use App\Models\Crm\FunnelStage;
use App\Models\Crm\Lead;
use App\Models\Crm\LostReason;
use App\Services\Crm\CalendarEventService;
use App\Services\Crm\FunnelService;
use App\Support\Crm\CrmRoutes;
use App\Support\Crm\CrmScope;
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

    /** @var Collection<int, FunnelStage> */
    public Collection $stages;

    public bool $showLostModal = false;

    public ?int $pendingLeadId = null;

    public ?int $pendingStageId = null;

    public ?int $lostReasonId = null;

    public string $lostReasonDetail = '';

    public bool $showCalendarSuggestion = false;

    public ?int $suggestionLeadId = null;

    public ?string $suggestionTitle = null;

    public ?string $suggestionEventType = null;

    public function mount(): void
    {
        $this->stages = collect();
        $this->loadBoard();
    }

    public function updatedFunnelId(): void
    {
        $this->loadBoard();
    }

    public function updatedLifecycleFilter(): void
    {
        $this->loadBoard();
    }

    public function requestMoveLead(int $leadId, int $stageId): void
    {
        abort_unless(auth()->user()?->hasPermission('pipeline.manage'), 403);

        $lead = CrmScope::leads(Lead::query())->findOrFail($leadId);
        $this->authorize('moveOnPipeline', $lead);

        $stage = FunnelStage::query()->findOrFail($stageId);

        if ($stage->is_lost) {
            $this->pendingLeadId = $leadId;
            $this->pendingStageId = $stageId;
            $this->lostReasonId = null;
            $this->lostReasonDetail = '';
            $this->showLostModal = true;

            return;
        }

        $this->moveLead($leadId, $stageId);
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

        $this->moveLead($this->pendingLeadId, $this->pendingStageId, $this->lostReasonId, $this->lostReasonDetail, $funnelService);
        $this->resetLostModal();
    }

    public function cancelLostMove(): void
    {
        $this->resetLostModal();
    }

    public function moveLead(int $leadId, int $stageId, ?int $lostReasonId = null, ?string $lostReasonDetail = null, ?FunnelService $funnelService = null): void
    {
        abort_unless(auth()->user()?->hasPermission('pipeline.manage'), 403);

        $lead = CrmScope::leads(Lead::query())->findOrFail($leadId);
        $this->authorize('moveOnPipeline', $lead);

        $stage = FunnelStage::query()->findOrFail($stageId);

        ($funnelService ?? app(FunnelService::class))->moveLead(
            $lead,
            $stage,
            auth()->user(),
            $lostReasonId,
            $lostReasonDetail,
        );

        $suggestion = app(CalendarEventService::class)->suggestForStage($stage);

        if ($suggestion && auth()->user()?->hasPermission('calendar.view')) {
            $this->suggestionLeadId = $leadId;
            $this->suggestionTitle = $suggestion['title'];
            $this->suggestionEventType = $suggestion['event_type_slug'];
            $this->showCalendarSuggestion = true;
        }

        $this->loadBoard();
    }

    public function dismissCalendarSuggestion(): void
    {
        $this->reset(['showCalendarSuggestion', 'suggestionLeadId', 'suggestionTitle', 'suggestionEventType']);
    }

    public function calendarSuggestionUrl(): ?string
    {
        if (! $this->suggestionLeadId) {
            return null;
        }

        return route(\App\Support\Crm\CrmRoutes::name('calendar.index'), [
            'lead' => $this->suggestionLeadId,
        ]);
    }

    public function leadProfileUrl(Lead $lead): string
    {
        return match ($lead->lifecycle) {
            LeadLifecycle::Prospect => CrmRoutes::url('prospects.show', ['lead' => $lead]),
            LeadLifecycle::Client => CrmRoutes::url('customers.show', ['lead' => $lead]),
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
            'lostReasons' => LostReason::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ])->layout($this->crmLayout());
    }

    private function loadBoard(): void
    {
        $this->stages = collect();

        if (! Schema::hasTable('funnels')) {
            return;
        }

        $funnel = $this->funnelId
            ? Funnel::query()->find($this->funnelId)
            : Funnel::query()->where('is_default', true)->where('is_active', true)->first();

        if (! $funnel) {
            return;
        }

        $this->funnelId = $funnel->id;

        $this->stages = $funnel->stages()
            ->with([
                'leads' => function ($query) {
                    $query = CrmScope::leads($query)->with('assignedUser')->orderByDesc('updated_at');

                    if ($this->lifecycleFilter !== '') {
                        $query->lifecycle($this->lifecycleFilter);
                    }

                    return $query;
                },
            ])
            ->get();
    }

    private function resetLostModal(): void
    {
        $this->reset(['showLostModal', 'pendingLeadId', 'pendingStageId', 'lostReasonId', 'lostReasonDetail']);
    }
}
