<?php

namespace App\Livewire\Crm;

use App\Enums\Crm\TaskPriority;
use App\Enums\Crm\TaskStatus;
use App\Livewire\Crm\Concerns\UsesCrmLayout;
use App\Models\Crm\Lead;
use App\Models\Crm\Task;
use App\Models\User;
use App\Services\Crm\TaskService;
use App\Support\Crm\BusinessLineScope;
use App\Support\Crm\CrmScope;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class TaskManagement extends Component
{
    use AuthorizesRequests;
    use UsesCrmLayout;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $priorityFilter = '';

    public string $assigneeFilter = '';

    public string $duePreset = '';

    public bool $showForm = false;

    public ?int $editingTaskId = null;

    public ?int $user_id = null;

    public ?int $lead_id = null;

    public string $title = '';

    public string $description = '';

    public string $priority = 'normal';

    public string $status = 'pending';

    public string $due_at = '';

    public string $reminder_at = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('tasks.assign'), 403);

        $this->user_id = auth()->id();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPriorityFilter(): void
    {
        $this->resetPage();
    }

    public function updatingAssigneeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDuePreset(): void
    {
        $this->resetPage();
    }

    public function openForm(?int $taskId = null): void
    {
        abort_unless(auth()->user()?->hasPermission('tasks.assign'), 403);

        if ($taskId) {
            $task = $this->manageableTasks()->findOrFail($taskId);
            $this->editingTaskId = $task->id;
            $this->user_id = $task->user_id;
            $this->lead_id = $task->lead_id;
            $this->title = $task->title;
            $this->description = $task->description ?? '';
            $this->priority = $task->priority?->value ?? 'normal';
            $this->status = $task->status?->value ?? 'pending';
            $this->due_at = $task->due_at?->format('Y-m-d\TH:i') ?? '';
            $this->reminder_at = $task->reminder_at?->format('Y-m-d\TH:i') ?? '';
        } else {
            $this->resetForm();
            $this->user_id = auth()->id();
        }

        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(TaskService $taskService): void
    {
        abort_unless(auth()->user()?->hasPermission('tasks.assign'), 403);

        $data = $this->validate($this->rules());

        if (! empty($data['lead_id'])) {
            $lead = CrmScope::leads(Lead::query())->findOrFail($data['lead_id']);
            $this->authorize('view', $lead);
        }

        $payload = array_merge($data, [
            'due_at' => $data['due_at'] ?: null,
            'reminder_at' => $data['reminder_at'] ?: null,
        ]);

        if ($this->editingTaskId) {
            $task = $this->manageableTasks()->findOrFail($this->editingTaskId);
            $taskService->update($task, $payload, auth()->user());
            session()->flash('status', 'Task updated.');
        } else {
            $taskService->create($payload, auth()->user());
            session()->flash('status', 'Task assigned.');
        }

        $this->closeForm();
    }

    public function completeTask(int $taskId, TaskService $taskService): void
    {
        abort_unless(auth()->user()?->hasPermission('tasks.assign'), 403);

        $task = $this->manageableTasks()->findOrFail($taskId);
        $taskService->complete($task, auth()->user());
        session()->flash('status', 'Task completed.');
    }

    public function deleteTask(int $taskId, TaskService $taskService): void
    {
        abort_unless(auth()->user()?->hasPermission('tasks.assign'), 403);

        $task = $this->manageableTasks()->findOrFail($taskId);
        $taskService->delete($task, auth()->user());
        session()->flash('status', 'Task deleted.');
    }

    public function render()
    {
        $tasks = $this->manageableTasks()
            ->with(['lead', 'user'])
            ->when($this->search, function ($query) {
                $query->where(function ($inner) {
                    $inner->where('title', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->priorityFilter, fn ($q) => $q->where('priority', $this->priorityFilter))
            ->when($this->assigneeFilter, fn ($q) => $q->where('user_id', $this->assigneeFilter))
            ->when($this->duePreset === 'today', function ($q) {
                $q->whereDate('due_at', today())
                    ->where('status', '!=', TaskStatus::Completed->value);
            })
            ->when($this->duePreset === 'overdue', function ($q) {
                $q->whereNotNull('due_at')
                    ->where('due_at', '<', now()->startOfDay())
                    ->where('status', '!=', TaskStatus::Completed->value);
            })
            ->when($this->duePreset === 'upcoming', function ($q) {
                $q->whereNotNull('due_at')
                    ->where('due_at', '>', now()->endOfDay())
                    ->where('status', '!=', TaskStatus::Completed->value);
            })
            ->orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END")
            ->orderBy('due_at')
            ->paginate(config('crm.pagination.tasks', 20));

        return view('livewire.crm.task-management', [
            'tasks' => $tasks,
            'members' => $this->assignableMembers(),
            'leads' => CrmScope::leads(Lead::query())->orderBy('first_name')->limit(200)->get(['id', 'first_name', 'last_name', 'email']),
            'priorities' => TaskPriority::cases(),
            'statuses' => TaskStatus::cases(),
        ])->layout($this->crmLayout(), ['header' => 'Tasks Management']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Task>
     */
    private function manageableTasks()
    {
        return BusinessLineScope::apply(Task::query());
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function assignableMembers()
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'lead_id' => ['nullable', 'exists:leads,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(array_column(TaskPriority::cases(), 'value'))],
            'status' => ['required', Rule::in(array_column(TaskStatus::cases(), 'value'))],
            'due_at' => ['nullable', 'date'],
            'reminder_at' => ['nullable', 'date'],
        ];
    }

    private function resetForm(): void
    {
        $this->reset(['editingTaskId', 'lead_id', 'title', 'description', 'due_at', 'reminder_at']);
        $this->priority = 'normal';
        $this->status = 'pending';
        $this->user_id = auth()->id();
    }
}
