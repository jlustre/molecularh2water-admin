<?php

namespace App\Livewire\Portal;

use App\Enums\Crm\TaskPriority;
use App\Services\Portal\PortalDemoService;
use App\Services\Portal\PortalTaskService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class TasksModal extends Component
{
    public bool $show = false;

    public string $contact_search = '';

    public ?int $lead_id = null;

    public string $title = '';

    public string $description = '';

    public string $priority = 'normal';

    public string $task_when = 'tomorrow_10';

    #[On('open-tasks')]
    public function open(): void
    {
        abort_unless(auth()->user()?->hasPermission('tasks.view'), 403);

        $this->resetForm();
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->resetForm();
    }

    public function updatedContactSearch(): void
    {
        $this->lead_id = null;
    }

    public function selectContact(int $leadId, PortalDemoService $demos): void
    {
        $lead = $demos->contactById($leadId, Auth::user());

        if (! $lead) {
            return;
        }

        $this->lead_id = $lead->id;
        $this->contact_search = $lead->fullName();
    }

    public function clearContact(): void
    {
        $this->contact_search = '';
        $this->lead_id = null;
    }

    public function create(PortalTaskService $tasks): void
    {
        abort_unless(auth()->user()?->hasPermission('tasks.manage'), 403);

        $this->validate($this->fieldRules());

        if (! $this->lead_id && filled(trim($this->contact_search))) {
            if ($match = app(PortalDemoService::class)->findContactMatch($this->contact_search, Auth::user())) {
                $this->lead_id = $match->id;
            }
        }

        $tasks->create([
            'lead_id' => $this->lead_id,
            'title' => $this->title,
            'description' => $this->description ?: null,
            'priority' => $this->priority,
            'task_when' => $this->task_when,
        ], Auth::user());

        $this->resetForm();
        $this->dispatch('task-created');
        session()->flash('task_status', $this->task_when === 'none'
            ? 'Task created.'
            : 'Task created and synced to your calendar.');
    }

    public function completeTask(int $taskId, PortalTaskService $tasks): void
    {
        abort_unless(auth()->user()?->hasPermission('tasks.manage'), 403);

        $task = $tasks->findTask($taskId, Auth::user());

        if (! $task || $task->status?->value === 'completed') {
            return;
        }

        $tasks->complete($task, Auth::user());
        $this->dispatch('task-created');
        session()->flash('task_status', 'Task marked complete.');
    }

    public function render(PortalTaskService $tasks, PortalDemoService $demos)
    {
        return view('livewire.portal.tasks-modal', [
            'upcomingTasks' => $tasks->upcomingTasks(),
            'contactResults' => $demos->searchContacts($this->contact_search),
            'priorities' => TaskPriority::cases(),
            'showContactResults' => ! $this->lead_id
                && strlen(trim($this->contact_search)) >= 3,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(array_column(TaskPriority::cases(), 'value'))],
            'task_when' => ['required', 'in:none,in_15,in_30,in_60,today_14,today_16,tomorrow_10,tomorrow_14,next_week'],
        ];
    }

    private function resetForm(): void
    {
        $this->contact_search = '';
        $this->lead_id = null;
        $this->title = '';
        $this->description = '';
        $this->priority = 'normal';
        $this->task_when = 'tomorrow_10';
        $this->resetValidation();
    }
}
