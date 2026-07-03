<?php

namespace App\Livewire\Crm;

use App\Models\Crm\Activity;
use App\Models\Crm\ActivityType;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Services\Crm\ActivityService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class LeadActivitiesPanel extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public Lead|Prospect|Customer|Recruit $lead;

    public bool $showLogForm = false;

    public ?int $activity_type_id = null;

    public string $title = '';

    public string $description = '';

    public string $outcome = '';

    public string $next_action = '';

    public string $completed_at = '';

    public string $next_follow_up_at = '';

    public ?int $duration_minutes = null;

    public function mount(Lead|Prospect|Customer|Recruit $lead): void
    {
        $this->authorize('view', $lead);
        $this->lead = $lead;
        $this->completed_at = now()->format('Y-m-d\TH:i');
    }

    public function toggleLogForm(): void
    {
        $this->authorize('update', $this->lead);
        $this->showLogForm = ! $this->showLogForm;

        if (! $this->showLogForm) {
            $this->resetForm();
        }
    }

    public function logActivity(ActivityService $activityService): void
    {
        $this->authorize('update', $this->lead);

        $data = $this->validate($this->rules());

        $activityService->log([
            'contact_type' => $this->lead->getMorphClass(),
            'contact_id' => $this->lead->id,
            'activity_type_id' => $data['activity_type_id'],
            'title' => $data['title'] ?: null,
            'description' => $data['description'] ?: null,
            'outcome' => $data['outcome'] ?: null,
            'next_action' => $data['next_action'] ?: null,
            'completed_at' => $data['completed_at'] ?: now(),
            'next_follow_up_at' => $data['next_follow_up_at'] ?: null,
            'duration_minutes' => $data['duration_minutes'],
        ], auth()->user());

        $this->lead->refresh();
        $this->resetForm();
        $this->showLogForm = false;
        $this->resetPage();
        $this->dispatch('activity-logged');
    }

    public function deleteActivity(int $activityId, ActivityService $activityService): void
    {
        $this->authorize('update', $this->lead);

        $activity = Activity::query()
            ->where('contact_type', $this->lead->getMorphClass())
            ->where('contact_id', $this->lead->id)
            ->findOrFail($activityId);

        $activityService->delete($activity, auth()->user());

        $this->lead->refresh();
        $this->resetPage();
        $this->dispatch('activity-logged');
    }

    #[On('activity-logged')]
    public function refreshActivities(): void
    {
        $this->lead->refresh();
    }

    public function render()
    {
        return view('livewire.crm.lead-activities-panel', [
            'activities' => $this->lead->activities()
                ->with(['type', 'user'])
                ->latest('completed_at')
                ->latest('id')
                ->paginate(config('crm.pagination.activities', 10)),
            'types' => ActivityType::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'outcomes' => config('crm.activity_outcomes', []),
            'canLog' => auth()->user()?->can('update', $this->lead) ?? false,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'activity_type_id' => ['required', 'exists:activity_types,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'outcome' => ['nullable', Rule::in(array_keys(config('crm.activity_outcomes', [])))],
            'next_action' => ['nullable', 'string', 'max:255'],
            'completed_at' => ['nullable', 'date'],
            'next_follow_up_at' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:480'],
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'activity_type_id',
            'title',
            'description',
            'outcome',
            'next_action',
            'duration_minutes',
        ]);
        $this->completed_at = now()->format('Y-m-d\TH:i');
        $this->next_follow_up_at = '';
    }
}
