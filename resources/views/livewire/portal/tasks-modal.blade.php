<div>
    @if ($show)
        <div class="shell-modal-overlay fixed inset-0 flex items-center justify-center overflow-y-auto px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="tasks-title">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative mx-auto w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-amber-700">Quick Action</p>
                        <h2 id="tasks-title" class="mt-1 text-xl font-black text-slate-950">Tasks</h2>
                        <p class="mt-1 text-sm text-slate-500">Synced with your CRM calendar when a due date is set.</p>
                    </div>
                    <button type="button" wire:click="close" class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                @if (session('task_status'))
                    <div class="mx-5 mt-4 rounded-lg border border-amber-100 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 sm:mx-6">
                        {{ session('task_status') }}
                    </div>
                @endif

                <div class="max-h-[70vh] overflow-y-auto px-5 py-5 sm:px-6">
                    <section>
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h3 class="text-sm font-bold text-slate-900">Open tasks</h3>
                            <a href="{{ route(\App\Support\Crm\CrmRoutes::name('tasks.index')) }}" class="text-xs font-semibold text-amber-700 hover:text-amber-900">
                                Open Tasks
                            </a>
                        </div>

                        <ul class="space-y-2">
                            @forelse ($upcomingTasks as $task)
                                @php
                                    $isOverdue = $task->due_at && $task->due_at->isPast();
                                @endphp
                                <li @class([
                                    'flex items-start gap-2 rounded-xl border px-3 py-2.5 sm:gap-3',
                                    'border-rose-100 bg-rose-50/60' => $isOverdue,
                                    'border-slate-100 bg-slate-50' => ! $isOverdue,
                                ])>
                                    @if (auth()->user()?->hasPermission('tasks.manage'))
                                        <input
                                            type="checkbox"
                                            class="mt-0.5 shrink-0 rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                                            wire:click.prevent="completeTask({{ $task->id }})"
                                        >
                                    @endif
                                    <div class="min-w-0 flex-1 space-y-0.5">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $task->title }}</p>
                                        <p class="truncate text-xs text-slate-500">
                                            @if ($task->due_at)
                                                {{ $task->due_at->format('M j · g:i A') }}
                                            @else
                                                No due date
                                            @endif
                                            · {{ $task->priority?->label() }}
                                            @if ($task->lead)
                                                · {{ $task->lead->fullName() }}
                                            @endif
                                        </p>
                                    </div>
                                </li>
                            @empty
                                <li class="rounded-xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">
                                    No open tasks yet.
                                </li>
                            @endforelse
                        </ul>
                    </section>

                    <div class="my-6 border-t border-slate-200"></div>

                    @if (auth()->user()?->hasPermission('tasks.manage'))
                        <section>
                            <h3 class="text-sm font-bold text-slate-900">Create a task</h3>
                            <p class="mt-1 text-xs text-slate-500">Add a follow-up or to-do. Set a due time to place it on your calendar.</p>

                            <form class="mt-4 space-y-4" wire:submit="create">
                                <div>
                                    <label for="task-title" class="mb-1 block text-sm font-semibold text-slate-700">Title</label>
                                    <input
                                        id="task-title"
                                        type="text"
                                        wire:model="title"
                                        placeholder="Call back about pricing"
                                        class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                    />
                                    @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="relative">
                                    <label for="task-contact-search" class="mb-1 block text-sm font-semibold text-slate-700">
                                        Link to contact (optional)
                                    </label>
                                    <input
                                        id="task-contact-search"
                                        type="search"
                                        wire:model.live.debounce.300ms="contact_search"
                                        placeholder="Type at least 3 characters…"
                                        class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                        @disabled($lead_id)
                                        autocomplete="off"
                                    />
                                    @if ($lead_id)
                                        <div class="mt-2 flex items-center justify-between gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
                                            <p class="text-sm font-semibold text-amber-900">{{ $contact_search }}</p>
                                            <button type="button" wire:click="clearContact" class="text-xs font-semibold text-amber-700 hover:text-amber-900">
                                                Remove
                                            </button>
                                        </div>
                                    @endif

                                    @if ($showContactResults)
                                        <ul class="absolute z-10 mt-1 max-h-48 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                                            @forelse ($contactResults as $lead)
                                                <li>
                                                    <button
                                                        type="button"
                                                        wire:click="selectContact({{ $lead->id }})"
                                                        class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-amber-50"
                                                    >
                                                        <span class="font-semibold text-slate-900">{{ $lead->fullName() }}</span>
                                                        <span @class([
                                                            'shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide',
                                                            'bg-amber-100 text-amber-800' => $lead->lifecycle?->value === 'prospect',
                                                            'bg-emerald-100 text-emerald-800' => $lead->lifecycle?->value === 'client',
                                                        ])>
                                                            {{ $lead->lifecycle?->value === 'client' ? 'Customer' : 'Prospect' }}
                                                        </span>
                                                    </button>
                                                </li>
                                            @empty
                                                <li class="px-3 py-2 text-xs text-slate-500">No matching contacts.</li>
                                            @endforelse
                                        </ul>
                                    @endif
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="task-priority" class="mb-1 block text-sm font-semibold text-slate-700">Priority</label>
                                        <select id="task-priority" wire:model="priority" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                            @foreach ($priorities as $priorityOption)
                                                <option value="{{ $priorityOption->value }}">{{ $priorityOption->label() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="task-when" class="mb-1 block text-sm font-semibold text-slate-700">Due</label>
                                        <select id="task-when" wire:model="task_when" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                            <option value="none">No due date</option>
                                            <option value="in_15">In 15 minutes</option>
                                            <option value="in_30">In 30 minutes</option>
                                            <option value="in_60">In 1 hour</option>
                                            <option value="today_14">Today at 2:00 PM</option>
                                            <option value="today_16">Today at 4:00 PM</option>
                                            <option value="tomorrow_10">Tomorrow at 10:00 AM</option>
                                            <option value="tomorrow_14">Tomorrow at 2:00 PM</option>
                                            <option value="next_week">Next Monday at 10:00 AM</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label for="task-description" class="mb-1 block text-sm font-semibold text-slate-700">Notes</label>
                                    <textarea
                                        id="task-description"
                                        wire:model="description"
                                        rows="2"
                                        class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                        placeholder="Details, context, or next steps"
                                    ></textarea>
                                </div>

                                <div class="flex justify-end pt-2">
                                    <button type="submit" class="rounded-full bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-700">
                                        Create task
                                    </button>
                                </div>
                            </form>
                        </section>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
