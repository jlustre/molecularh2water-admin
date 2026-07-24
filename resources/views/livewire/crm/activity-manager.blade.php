<div class="p-4 sm:p-6 lg:p-8">
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-600">CRM</p>
            <h1 class="mt-1 text-3xl font-bold text-slate-900">Activities</h1>
            <p class="mt-1 text-sm text-slate-500">Log calls, emails, meetings, and other sales touchpoints.</p>
        </div>
        @if (auth()->user()?->hasPermission('activities.manage'))
            <button
                class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-teal-600 to-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm"
                type="button"
                wire:click="openForm"
            >
                Log Activity
            </button>
        @endif
    </div>

    <div class="mb-6 grid gap-4 xl:grid-cols-3">
        <div class="xl:col-span-1">
            <livewire:crm.consultant-performance-panel />
        </div>
        <div class="xl:col-span-2">
            <livewire:crm.consultant-performance-summary />
        </div>
    </div>

    <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-5">
        <input
            class="rounded-xl border-slate-200 shadow-sm focus:border-teal-500 focus:ring-teal-500"
            placeholder="Search activity or contact..."
            type="search"
            wire:model.live.debounce.300ms="search"
        />
        <select class="rounded-xl border-slate-200 shadow-sm" wire:model.live="typeId">
            <option value="">All types</option>
            @foreach ($types as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
        </select>
        <select class="rounded-xl border-slate-200 shadow-sm" wire:model.live="lead_id">
            <option value="">All contacts</option>
            @foreach ($leads as $lead)
                <option value="{{ $lead->id }}">{{ $lead->fullName() }}</option>
            @endforeach
        </select>
        <input
            class="rounded-xl border-slate-200 shadow-sm"
            type="date"
            wire:model.live="dateFrom"
            title="From date"
        />
        <input
            class="rounded-xl border-slate-200 shadow-sm"
            type="date"
            wire:model.live="dateTo"
            title="To date"
        />
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">When</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Contact</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Details</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Consultant</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($activities as $activity)
                    <tr wire:key="activity-{{ $activity->id }}">
                        <td class="px-4 py-3 text-sm text-slate-600">
                            {{ $activity->completed_at?->format('M j, Y g:i A') ?? $activity->created_at->format('M j, Y') }}
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $activity->type?->name }}</td>
                        <td class="px-4 py-3 text-sm text-teal-700">{{ $activity->lead?->fullName() ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            <p class="font-medium text-slate-900">{{ $activity->title }}</p>
                            @if ($activity->outcome)
                                <p class="text-xs capitalize text-slate-500">Outcome: {{ str_replace('_', ' ', $activity->outcome) }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $activity->user?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            @if (auth()->user()?->hasPermission('activities.manage'))
                                <button
                                    class="font-semibold text-teal-700 hover:text-teal-900"
                                    type="button"
                                    wire:click="openForm({{ $activity->id }})"
                                >
                                    Edit
                                </button>
                                <button
                                    class="ml-3 font-semibold text-rose-600 hover:text-rose-800"
                                    type="button"
                                    wire:click="deleteActivity({{ $activity->id }})"
                                    wire:confirm="Delete this activity?"
                                >
                                    Delete
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-10 text-center text-sm text-slate-500" colspan="6">No activities logged yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $activities->links() }}</div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">
                <h3 class="text-lg font-bold text-slate-900">{{ $editingId ? 'Edit Activity' : 'Log Activity' }}</h3>
                <form class="mt-4 space-y-4" wire:submit="save">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Contact *</label>
                        <select class="w-full rounded-xl border-slate-200 shadow-sm" wire:model="lead_id">
                            <option value="">Select contact</option>
                            @foreach ($leads as $lead)
                                <option value="{{ $lead->id }}">{{ $lead->fullName() }}</option>
                            @endforeach
                        </select>
                        @error('lead_id') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Activity Type *</label>
                        <select class="w-full rounded-xl border-slate-200 shadow-sm" wire:model="activity_type_id">
                            <option value="">Select type</option>
                            @foreach ($types as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                        @error('activity_type_id') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Title</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="title" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Description</label>
                        <textarea class="w-full rounded-xl border-slate-200 shadow-sm" rows="3" wire:model="description"></textarea>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Outcome</label>
                            <select class="w-full rounded-xl border-slate-200 shadow-sm" wire:model="outcome">
                                <option value="">—</option>
                                @foreach ($outcomes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Duration (minutes)</label>
                            <input class="w-full rounded-xl border-slate-200 shadow-sm" min="1" type="number" wire:model="duration_minutes" />
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Next Action</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="next_action" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Completed At</label>
                            <input class="w-full rounded-xl border-slate-200 shadow-sm" type="datetime-local" wire:model="completed_at" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Next Follow-Up</label>
                            <input class="w-full rounded-xl border-slate-200 shadow-sm" type="datetime-local" wire:model="next_follow_up_at" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700" type="button" wire:click="closeForm">Cancel</button>
                        <button class="rounded-full bg-teal-600 px-4 py-2 text-sm font-semibold text-white" type="submit">
                            {{ $editingId ? 'Save Changes' : 'Create Activity' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
