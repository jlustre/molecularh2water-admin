<?php

namespace App\Livewire\Crm;

use App\Livewire\Crm\Concerns\UsesCrmLayout;
use App\Models\Crm\Activity;
use App\Models\Crm\ActivityType;
use App\Models\Crm\Lead;
use App\Services\Crm\ActivityService;
use App\Support\Crm\CrmScope;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityManager extends Component
{
    use AuthorizesRequests;
    use UsesCrmLayout;
    use WithPagination;

    public string $search = '';

    public string $typeId = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public ?int $lead_id = null;

    public ?int $activity_type_id = null;

    public string $title = '';

    public string $description = '';

    public string $outcome = '';

    public string $next_action = '';

    public string $completed_at = '';

    public string $next_follow_up_at = '';

    public ?int $duration_minutes = null;

    public function mount(?int $lead = null): void
    {
        abort_unless(auth()->user()?->hasPermission('activities.view'), 403);

        if ($lead) {
            $this->lead_id = $lead;
        }

        $this->completed_at = now()->format('Y-m-d\TH:i');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function openForm(?int $activityId = null): void
    {
        abort_unless(auth()->user()?->hasPermission('activities.manage'), 403);

        if ($activityId) {
            $activity = CrmScope::activities(Activity::query())->with('lead')->findOrFail($activityId);
            $this->editingId = $activity->id;
            $this->lead_id = $activity->lead_id;
            $this->activity_type_id = $activity->activity_type_id;
            $this->title = $activity->title ?? '';
            $this->description = $activity->description ?? '';
            $this->outcome = $activity->outcome ?? '';
            $this->next_action = $activity->next_action ?? '';
            $this->completed_at = $activity->completed_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
            $this->next_follow_up_at = $activity->lead?->next_follow_up_at?->format('Y-m-d\TH:i') ?? '';
            $this->duration_minutes = $activity->duration_minutes;
        } else {
            $this->resetForm();
        }

        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(ActivityService $activityService): void
    {
        abort_unless(auth()->user()?->hasPermission('activities.manage'), 403);

        $data = $this->validate($this->rules());
        $lead = CrmScope::leads(Lead::query())->findOrFail($data['lead_id']);
        $this->authorize('view', $lead);

        $payload = array_merge($data, [
            'completed_at' => $data['completed_at'] ?: now(),
            'next_follow_up_at' => $data['next_follow_up_at'] ?: null,
        ]);

        if ($this->editingId) {
            $activity = CrmScope::activities(Activity::query())->findOrFail($this->editingId);
            $activityService->update($activity, $payload, auth()->user());
            session()->flash('status', 'Activity updated.');
        } else {
            $activityService->log($payload, auth()->user());
            session()->flash('status', 'Activity logged successfully.');
        }

        $this->closeForm();
    }

    public function deleteActivity(int $activityId, ActivityService $activityService): void
    {
        abort_unless(auth()->user()?->hasPermission('activities.manage'), 403);

        $activity = CrmScope::activities(Activity::query())->with('lead')->findOrFail($activityId);

        if ($activity->lead) {
            $this->authorize('view', $activity->lead);
        }

        $activityService->delete($activity, auth()->user());
        session()->flash('status', 'Activity deleted.');
    }

    public function render()
    {
        $activities = CrmScope::activities(Activity::query())
            ->with(['type', 'lead', 'user'])
            ->when($this->search, function ($query) {
                $query->where(function ($inner) {
                    $inner->where('title', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhereHas('lead', function ($leadQuery) {
                            $leadQuery->where('first_name', 'like', "%{$this->search}%")
                                ->orWhere('last_name', 'like', "%{$this->search}%")
                                ->orWhere('email', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->typeId, fn ($q) => $q->where('activity_type_id', $this->typeId))
            ->when($this->lead_id, fn ($q) => $q->whereLeadId($this->lead_id))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('completed_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('completed_at', '<=', $this->dateTo))
            ->latest('completed_at')
            ->paginate(config('crm.pagination.activities', 20));

        return view('livewire.crm.activity-manager', [
            'activities' => $activities,
            'types' => ActivityType::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'leads' => CrmScope::leads(Lead::query())->orderBy('first_name')->limit(200)->get(['id', 'first_name', 'last_name', 'email']),
            'outcomes' => config('crm.activity_outcomes', []),
        ])->layout($this->crmLayout());
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'lead_id' => ['required', 'exists:leads,id'],
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
            'editingId',
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
