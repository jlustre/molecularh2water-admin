<div class="p-4 sm:p-6 lg:p-8">
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-600">My Workspace</p>
            <h1 class="mt-1 text-3xl font-bold text-slate-900">My Tasks</h1>
            <p class="mt-1 text-sm text-slate-500">Track to-dos, priorities, due dates, and reminders.</p>
        </div>
        @if (auth()->user()?->hasPermission('tasks.manage'))
            <button
                class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-teal-600 to-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm"
                type="button"
                wire:click="openForm"
            >
                Add Task
            </button>
        @endif
    </div>

    <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-3">
        <input class="rounded-xl border-slate-200 shadow-sm" placeholder="Search tasks..." type="search" wire:model.live.debounce.300ms="search" />
        <select class="rounded-xl border-slate-200 shadow-sm" wire:model.live="statusFilter">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
        <select class="rounded-xl border-slate-200 shadow-sm" wire:model.live="priorityFilter">
            <option value="">All priorities</option>
            @foreach ($priorities as $priority)
                <option value="{{ $priority->value }}">{{ $priority->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach ([
            '' => 'All due dates',
            'today' => 'Due today',
            'overdue' => 'Overdue',
            'upcoming' => 'Upcoming',
        ] as $value => $label)
            <button
                @class([
                    'rounded-full px-4 py-2 text-sm font-semibold shadow-sm',
                    'bg-teal-600 text-white' => $duePreset === $value,
                    'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' => $duePreset !== $value,
                ])
                type="button"
                wire:click="$set('duePreset', '{{ $value }}')"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Task</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Contact</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Priority</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Due</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($tasks as $task)
                    <tr wire:key="task-{{ $task->id }}">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-900">{{ $task->title }}</p>
                            @if ($task->description)
                                <p class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($task->description, 80) }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-teal-700">{{ $task->lead?->fullName() ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm capitalize text-slate-600">{{ $task->priority?->label() }}</td>
                        <td class="px-4 py-3 text-sm capitalize text-slate-600">{{ $task->status?->label() }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $task->due_at?->format('M j, Y g:i A') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            @if (auth()->user()?->hasPermission('tasks.manage'))
                                @if ($task->status?->value !== 'completed')
                                    <button class="font-semibold text-emerald-700 hover:text-emerald-900" type="button" wire:click="completeTask({{ $task->id }})">Complete</button>
                                @endif
                                <button class="ml-3 font-semibold text-teal-700 hover:text-teal-900" type="button" wire:click="openForm({{ $task->id }})">Edit</button>
                                <button class="ml-3 font-semibold text-rose-600 hover:text-rose-800" type="button" wire:click="deleteTask({{ $task->id }})" wire:confirm="Delete this task?">Delete</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-10 text-center text-sm text-slate-500" colspan="6">No tasks yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $tasks->links() }}</div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">
                <h3 class="text-lg font-bold text-slate-900">{{ $editingTaskId ? 'Edit Task' : 'New Task' }}</h3>
                <form class="mt-4 space-y-4" wire:submit="save">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Title *</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="title" />
                        @error('title') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Contact</label>
                        <select class="w-full rounded-xl border-slate-200 shadow-sm" wire:model="lead_id">
                            <option value="">No linked contact</option>
                            @foreach ($leads as $lead)
                                <option value="{{ $lead->id }}">{{ $lead->fullName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Description</label>
                        <textarea class="w-full rounded-xl border-slate-200 shadow-sm" rows="3" wire:model="description"></textarea>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Priority</label>
                            <select class="w-full rounded-xl border-slate-200 shadow-sm" wire:model="priority">
                                @foreach ($priorities as $priority)
                                    <option value="{{ $priority->value }}">{{ $priority->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Status</label>
                            <select class="w-full rounded-xl border-slate-200 shadow-sm" wire:model="status">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Due At</label>
                            <input class="w-full rounded-xl border-slate-200 shadow-sm" type="datetime-local" wire:model="due_at" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Reminder At</label>
                            <input class="w-full rounded-xl border-slate-200 shadow-sm" type="datetime-local" wire:model="reminder_at" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700" type="button" wire:click="closeForm">Cancel</button>
                        <button class="rounded-full bg-teal-600 px-4 py-2 text-sm font-semibold text-white" type="submit">Save Task</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
