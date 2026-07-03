<?php

namespace App\Livewire\Crm;

use App\Enums\Crm\DemonstrationOutcome;
use App\Enums\Crm\DemonstrationStatus;
use App\Enums\Crm\DemonstrationType;
use App\Models\Crm\Demonstration;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Services\Crm\DemonstrationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class LeadDemonstrationsPanel extends Component
{
    use AuthorizesRequests;

    public Lead|Prospect|Customer|Recruit $lead;

    public bool $showScheduleForm = false;

    public string $type = 'home';

    public string $scheduled_at = '';

    public int $duration_minutes = 60;

    public string $venue = '';

    public string $notes = '';

    public ?int $completingDemoId = null;

    public string $complete_outcome = 'interested';

    public bool $complete_attended = true;

    public string $complete_notes = '';

    public function mount(Lead|Prospect|Customer|Recruit $lead): void
    {
        $this->authorize('view', $lead);
        $this->lead = $lead;
        $this->scheduled_at = now()->addDay()->format('Y-m-d\TH:i');
    }

    public function toggleScheduleForm(): void
    {
        $this->authorize('update', $this->lead);
        $this->showScheduleForm = ! $this->showScheduleForm;
    }

    public function scheduleDemo(DemonstrationService $demonstrations): void
    {
        $this->authorize('update', $this->lead);

        $validated = $this->validate([
            'type' => ['required', Rule::enum(DemonstrationType::class)],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'venue' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $demonstrations->schedule($this->lead, $validated, auth()->user());

        $this->reset(['showScheduleForm', 'venue', 'notes']);
        $this->type = DemonstrationType::Home->value;
        $this->duration_minutes = 60;
        $this->scheduled_at = now()->addDay()->format('Y-m-d\TH:i');
        $this->lead->refresh();
    }

    public function startComplete(int $demoId): void
    {
        $this->authorize('update', $this->lead);
        $this->completingDemoId = $demoId;
        $this->complete_outcome = DemonstrationOutcome::Interested->value;
        $this->complete_attended = true;
        $this->complete_notes = '';
    }

    public function cancelComplete(): void
    {
        $this->completingDemoId = null;
    }

    public function completeDemo(DemonstrationService $demonstrations): void
    {
        $this->authorize('update', $this->lead);

        $validated = $this->validate([
            'completingDemoId' => ['required', 'integer'],
            'complete_outcome' => ['required', Rule::enum(DemonstrationOutcome::class)],
            'complete_attended' => ['boolean'],
            'complete_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $demo = Demonstration::query()
            ->whereContact($this->lead)
            ->findOrFail($validated['completingDemoId']);

        $demonstrations->complete($demo, [
            'outcome' => $validated['complete_outcome'],
            'attended' => $validated['complete_attended'],
            'notes' => $validated['complete_notes'] ?: $demo->notes,
        ], auth()->user());

        $this->completingDemoId = null;
        $this->lead->refresh();
    }

    public function render()
    {
        return view('livewire.crm.lead-demonstrations-panel', [
            'demonstrations' => $this->lead->demonstrations()->with('demonstrator')->limit(10)->get(),
            'demoTypes' => DemonstrationType::cases(),
            'demoStatuses' => DemonstrationStatus::cases(),
            'demoOutcomes' => DemonstrationOutcome::cases(),
        ]);
    }
}
