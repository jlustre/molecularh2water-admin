<?php

namespace App\Livewire\Crm;

use App\Enums\Crm\LeadLifecycle;
use App\Enums\Crm\LeadStatus;
use App\Livewire\Crm\Concerns\UsesCrmLayout;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\Crm\LeadSource;
use App\Services\Crm\LeadService;
use App\Support\Crm\CrmContactResolver;
use App\Support\Crm\CrmRoutes;
use App\Support\Crm\CrmScope;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class LeadTable extends Component
{
    use AuthorizesRequests;
    use UsesCrmLayout;
    use WithPagination;

    public LeadLifecycle $lifecycle = LeadLifecycle::Lead;

    public string $search = '';

    public string $temperature = '';

    public string $sourceId = '';

    public string $status = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'temperature' => ['except' => ''],
        'sourceId' => ['except' => ''],
        'status' => ['except' => ''],
    ];

    public function mount(LeadLifecycle|string $lifecycle = LeadLifecycle::Lead): void
    {
        $this->lifecycle = $lifecycle instanceof LeadLifecycle
            ? $lifecycle
            : LeadLifecycle::from($lifecycle);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function deleteLead(int $leadId, LeadService $leadService): void
    {
        $contact = CrmScope::contacts(CrmContactResolver::queryFor($this->lifecycle))->findOrFail($leadId);
        $this->authorize('delete', $contact);

        $leadService->delete($contact, auth()->user());

        session()->flash('status', 'Record deleted.');
    }

    public function convertToProspect(int $leadId, LeadService $leadService): void
    {
        $contact = CrmScope::contacts(CrmContactResolver::queryFor($this->lifecycle))->findOrFail($leadId);
        $this->authorize('update', $contact);

        abort_unless($this->canConvertLeadToProspect($contact), 403);

        $prospect = $leadService->convertLifecycle($contact, LeadLifecycle::Prospect, auth()->user());

        session()->flash('status', 'Converted to Prospect.');

        $this->redirect(CrmRoutes::url('prospects.show', ['lead' => $prospect]), navigate: true);
    }

    public function canConvertLeadToProspect(Lead|Prospect|Customer|Recruit|null $lead = null): bool
    {
        if ($this->lifecycle !== LeadLifecycle::Lead) {
            return false;
        }

        return $lead?->lifecycleSlug() === LeadLifecycle::Lead
            && auth()->user()?->hasPermission('prospects.manage');
    }

    public function canCreate(): bool
    {
        $user = auth()->user();

        return $user?->can('createForLifecycle', [Lead::class, $this->lifecycle]) ?? false;
    }

    public function createUrl(): string
    {
        return match ($this->lifecycle) {
            LeadLifecycle::Prospect => CrmRoutes::url('prospects.create'),
            LeadLifecycle::Client => CrmRoutes::url('customers.create'),
            LeadLifecycle::Recruit => CrmRoutes::url('recruits.create'),
            default => CrmRoutes::url('leads.create'),
        };
    }

    public function showUrl(Lead|Prospect|Customer|Recruit $lead): string
    {
        return match ($this->lifecycle) {
            LeadLifecycle::Prospect => CrmRoutes::url('prospects.show', ['lead' => $lead]),
            LeadLifecycle::Client => CrmRoutes::url('customers.show', ['lead' => $lead]),
            LeadLifecycle::Recruit => CrmRoutes::url('recruits.show', ['lead' => $lead]),
            default => CrmRoutes::url('leads.show', ['lead' => $lead]),
        };
    }

    public function editUrl(Lead|Prospect|Customer|Recruit $lead): string
    {
        return match ($this->lifecycle) {
            LeadLifecycle::Prospect => CrmRoutes::url('prospects.edit', ['lead' => $lead]),
            LeadLifecycle::Client => CrmRoutes::url('customers.edit', ['lead' => $lead]),
            LeadLifecycle::Recruit => CrmRoutes::url('recruits.edit', ['lead' => $lead]),
            default => CrmRoutes::url('leads.edit', ['lead' => $lead]),
        };
    }

    public function render()
    {
        $leads = CrmScope::contacts(CrmContactResolver::queryFor($this->lifecycle))
            ->with(['source', 'assignedUser', 'stage'])
            ->when($this->search, function ($query) {
                $query->where(function ($inner) {
                    $inner->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%");
                });
            })
            ->when($this->temperature, fn ($q) => $q->where('temperature', $this->temperature))
            ->when($this->sourceId, fn ($q) => $q->where('lead_source_id', $this->sourceId))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate(config('crm.pagination.leads', 15));

        return view('livewire.crm.lead-table', [
            'leads' => $leads,
            'sources' => LeadSource::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'statuses' => LeadStatus::options(),
        ])->layout($this->crmLayout());
    }
}
