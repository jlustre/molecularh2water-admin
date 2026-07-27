<?php

namespace App\Livewire\Crm;

use App\Enums\Crm\EngagementType;
use App\Enums\Crm\LeadLifecycle;
use App\Livewire\Crm\Concerns\UsesCrmLayout;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\Crm\Note;
use App\Services\Crm\LeadService;
use App\Services\Crm\TimelineService;
use App\Support\Crm\CrmRoutes;
use Illuminate\Support\Str;
use Livewire\Component;

class LeadProfile extends Component
{
    use UsesCrmLayout;

    public Lead|Prospect|Customer|Recruit $lead;

    public string $noteBody = '';

    public function mount(Lead|Prospect|Customer|Recruit $lead): void
    {
        $this->authorize('view', $lead);
        $this->lead = $lead->load(['source', 'stage', 'lostReason', 'assignedUser', 'tags', 'funnel', 'referredBy']);
    }

    public function addNote(TimelineService $timeline): void
    {
        $this->authorize('update', $this->lead);

        $validated = $this->validate([
            'noteBody' => ['required', 'string', 'max:5000'],
        ]);

        $note = Note::query()->create([
            'noteable_type' => $this->lead->getMorphClass(),
            'noteable_id' => $this->lead->id,
            'user_id' => auth()->id(),
            'body' => trim($validated['noteBody']),
        ]);

        $timeline->log(
            $this->lead,
            'note_added',
            'Note added',
            Str::limit($note->body, 120),
            ['note_id' => $note->id],
        );

        $this->noteBody = '';
        $this->lead->refresh();
        $this->dispatch('note-added');
    }

    public function convertTo(LeadLifecycle $lifecycle, LeadService $leadService): void
    {
        $this->authorize('update', $this->lead);

        abort_unless($this->canConvertTo($lifecycle), 403);

        $this->lead = $leadService->convertLifecycle($this->lead, $lifecycle, auth()->user());

        if ($lifecycle === LeadLifecycle::Recruit && $this->lead instanceof Customer) {
            session()->flash('status', 'Marked as customer & recruit (type B).');

            $this->redirect(CrmRoutes::url('customers.show', ['lead' => $this->lead]), navigate: true);

            return;
        }

        session()->flash('status', "Converted to {$lifecycle->label()}.");

        $this->redirect(match ($lifecycle) {
            LeadLifecycle::Prospect => CrmRoutes::url('prospects.show', ['lead' => $this->lead]),
            LeadLifecycle::Client => CrmRoutes::url('customers.show', ['lead' => $this->lead]),
            LeadLifecycle::Recruit => CrmRoutes::url('recruits.show', ['lead' => $this->lead]),
            default => CrmRoutes::url('leads.show', ['lead' => $this->lead]),
        }, navigate: true);
    }

    public function deleteRecord(LeadService $leadService): void
    {
        $this->authorize('delete', $this->lead);

        $lifecycle = $this->lead->lifecycle;
        $leadService->delete($this->lead, auth()->user());

        session()->flash('status', 'Record deleted.');

        $this->redirect(match ($lifecycle) {
            LeadLifecycle::Prospect => CrmRoutes::url('prospects.index'),
            LeadLifecycle::Client => CrmRoutes::url('customers.index'),
            LeadLifecycle::Recruit => CrmRoutes::url('recruits.index'),
            default => CrmRoutes::url('leads.index'),
        }, navigate: true);
    }

    public function editUrl(): string
    {
        return match ($this->lead->lifecycle) {
            LeadLifecycle::Prospect => CrmRoutes::url('prospects.edit', ['lead' => $this->lead]),
            LeadLifecycle::Client => CrmRoutes::url('customers.edit', ['lead' => $this->lead]),
            LeadLifecycle::Recruit => CrmRoutes::url('recruits.edit', ['lead' => $this->lead]),
            default => CrmRoutes::url('leads.edit', ['lead' => $this->lead]),
        };
    }

    public function indexUrl(): string
    {
        return match ($this->lead->lifecycle) {
            LeadLifecycle::Prospect => CrmRoutes::url('prospects.index'),
            LeadLifecycle::Client => CrmRoutes::url('customers.index'),
            LeadLifecycle::Recruit => CrmRoutes::url('recruits.index'),
            default => CrmRoutes::url('leads.index'),
        };
    }

    public function canConvertToProspect(): bool
    {
        return $this->canConvertTo(LeadLifecycle::Prospect);
    }

    public function canConvertToClient(): bool
    {
        return $this->canConvertTo(LeadLifecycle::Client);
    }

    public function canConvertToRecruit(): bool
    {
        return $this->canConvertTo(LeadLifecycle::Recruit);
    }

    public function render()
    {
        $view = $this->lead->lifecycle === LeadLifecycle::Prospect
            ? 'livewire.crm.prospect-profile'
            : 'livewire.crm.lead-profile';

        return view($view, [
            'bestTimesToContact' => config('crm.prospect_best_times_to_contact', []),
        ])->layout($this->crmLayout());
    }

    private function canConvertTo(LeadLifecycle $lifecycle): bool
    {
        return match ($lifecycle) {
            LeadLifecycle::Prospect => $this->lead->lifecycle === LeadLifecycle::Lead
                && auth()->user()?->hasPermission('prospects.manage'),
            LeadLifecycle::Client => $this->lead->lifecycle === LeadLifecycle::Prospect
                && auth()->user()?->hasPermission('clients.manage'),
            LeadLifecycle::Lead => false,
            LeadLifecycle::Recruit => (
                (
                    $this->lead->lifecycle === LeadLifecycle::Client
                    && $this->lead instanceof Customer
                    && $this->lead->engagement_type !== EngagementType::Both
                    && auth()->user()?->hasPermission('clients.manage')
                )
                || (
                    in_array($this->lead->lifecycle, [LeadLifecycle::Lead, LeadLifecycle::Prospect], true)
                    && auth()->user()?->hasPermission('recruits.manage')
                )
            ),
        };
    }
}
