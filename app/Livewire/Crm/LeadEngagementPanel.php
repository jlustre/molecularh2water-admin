<?php

namespace App\Livewire\Crm;

use App\Models\Crm\ActivityType;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Services\Crm\ActivityService;
use App\Services\Crm\AppointmentService;
use App\Services\Crm\TaskService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class LeadEngagementPanel extends Component
{
    use AuthorizesRequests;

    public Lead|Prospect|Customer|Recruit $lead;

    public ?int $activity_type_id = null;

    public string $activity_description = '';

    public string $activity_outcome = '';

    public string $task_title = '';

    public string $task_due_at = '';

    public string $appointment_title = '';

    public string $appointment_starts_at = '';

    public function mount(Lead|Prospect|Customer|Recruit $lead): void
    {
        $this->authorize('view', $lead);
        $this->lead = $lead;
        $this->appointment_starts_at = now()->addDay()->setHour(10)->setMinute(0)->format('Y-m-d\TH:i');
    }

    public function logActivity(ActivityService $activityService): void
    {
        $this->authorize('update', $this->lead);

        $data = $this->validate([
            'activity_type_id' => ['required', 'exists:activity_types,id'],
            'activity_description' => ['nullable', 'string', 'max:5000'],
            'activity_outcome' => ['nullable', Rule::in(array_keys(config('crm.activity_outcomes', [])))],
        ]);

        $activityService->log([
            'lead_id' => $this->lead->id,
            'activity_type_id' => $data['activity_type_id'],
            'description' => $data['activity_description'],
            'outcome' => $data['activity_outcome'] ?: null,
        ], auth()->user());

        $this->reset(['activity_type_id', 'activity_description', 'activity_outcome']);
        $this->lead->refresh();
        $this->dispatch('engagement-updated');
        session()->flash('status', 'Activity logged.');
    }

    public function addTask(TaskService $taskService): void
    {
        $this->authorize('update', $this->lead);

        $data = $this->validate([
            'task_title' => ['required', 'string', 'max:255'],
            'task_due_at' => ['nullable', 'date'],
        ]);

        $taskService->create([
            'lead_id' => $this->lead->id,
            'title' => $data['task_title'],
            'due_at' => $data['task_due_at'] ?: null,
        ], auth()->user());

        $this->reset(['task_title', 'task_due_at']);
        $this->lead->refresh();
        $this->dispatch('engagement-updated');
        session()->flash('status', 'Task added.');
    }

    public function scheduleAppointment(AppointmentService $appointmentService): void
    {
        $this->authorize('update', $this->lead);

        $data = $this->validate([
            'appointment_title' => ['required', 'string', 'max:255'],
            'appointment_starts_at' => ['required', 'date'],
        ]);

        $appointmentService->create([
            'lead_id' => $this->lead->id,
            'title' => $data['appointment_title'],
            'starts_at' => $data['appointment_starts_at'],
            'meeting_type' => 'zoom',
        ], auth()->user());

        $this->reset(['appointment_title']);
        $this->appointment_starts_at = now()->addDay()->setHour(10)->setMinute(0)->format('Y-m-d\TH:i');
        $this->lead->refresh();
        $this->dispatch('engagement-updated');
        session()->flash('status', 'Appointment scheduled.');
    }

    public function render()
    {
        return view('livewire.crm.lead-engagement-panel', [
            'types' => ActivityType::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'outcomes' => config('crm.activity_outcomes', []),
        ]);
    }
}
