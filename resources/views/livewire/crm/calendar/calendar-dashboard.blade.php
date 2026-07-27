<div class="relative p-4 sm:p-6 lg:p-8" data-crm-calendar-scope>
    <x-crm.calendar-loading-overlay message="Loading calendar..." />
    @if ($statusMessage)
        <div
            class="mb-4 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800"
            wire:key="calendar-status-{{ md5($statusMessage) }}"
        >
            {{ $statusMessage }}
        </div>
    @endif

    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-600">{{ $personalOnly ? 'My Workspace' : 'Schedule' }}</p>
            <h1 class="mt-1 text-3xl font-bold text-slate-900">{{ $personalOnly ? 'My Calendar' : 'Team Calendar' }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                @if ($personalOnly)
                    Your calendars, appointments, demos, tasks, and meetings in one personal grid.
                @else
                    Cooking shows, water awareness events, demos, and follow-ups across the team.
                @endif
            </p>
        </div>
        @if ($canManage)
            <div class="flex flex-wrap items-center gap-2">
                <button
                    class="inline-flex items-center justify-center rounded-full border border-orange-200 bg-orange-50 px-4 py-2.5 text-sm font-semibold text-orange-800 shadow-sm hover:bg-orange-100"
                    type="button"
                    wire:click="openCreateShow('cooking-show')"
                >
                    + Cooking Show
                </button>
                <button
                    class="inline-flex items-center justify-center rounded-full border border-cyan-200 bg-cyan-50 px-4 py-2.5 text-sm font-semibold text-cyan-800 shadow-sm hover:bg-cyan-100"
                    type="button"
                    wire:click="openCreateShow('water-awareness-show')"
                >
                    + Water Awareness Show
                </button>
                <button
                    class="inline-flex items-center justify-center rounded-full bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-700"
                    type="button"
                    wire:click="openCreate"
                >
                    + New Event
                </button>
            </div>
        @endif
    </div>

    <div class="grid gap-6 xl:grid-cols-4">
        <div class="space-y-4 xl:col-span-1">
            <livewire:crm.calendar.calendar-widgets
                :filters="$filters"
                :can-manage="$canManage"
                wire:key="calendar-widgets-{{ md5(json_encode($filters)) }}"
            />

            @if ($personalOnly)
                <livewire:crm.calendar.user-calendars-panel wire:key="user-calendars-panel" />
            @endif

            <x-crm.calendar-panel title="Filters" tone="slate">
                <div class="mt-3 space-y-3 text-sm">
                    @if ($canAssign)
                        <div>
                            <label class="mb-1 block font-semibold text-slate-600">Consultant</label>
                            <select class="w-full rounded-xl border-slate-200 text-sm" wire:model.live.debounce.300ms="filter_user_id">
                                <option value="">All visible</option>
                                @foreach ($assignableUsers as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div>
                        <label class="mb-1 block font-semibold text-slate-600">Event type</label>
                        <select class="w-full rounded-xl border-slate-200 text-sm" wire:model.live.debounce.300ms="filter_event_type_id">
                            <option value="">All types</option>
                            @foreach ($eventTypesByCategory as $category => $types)
                                <optgroup label="{{ $category }}">
                                    @foreach ($types as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <label class="flex items-center gap-2 text-slate-700">
                        <input type="checkbox" wire:model.live="filter_shows_only" />
                        Shows only
                    </label>
                    <div>
                        <label class="mb-1 block font-semibold text-slate-600">Status</label>
                        <select class="w-full rounded-xl border-slate-200 text-sm" wire:model.live.debounce.300ms="filter_status">
                            <option value="">Any status</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}">{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="flex items-center gap-2 text-slate-700">
                        <input type="checkbox" wire:model.live="show_tasks" />
                        Show tasks
                    </label>
                    <label class="flex items-center gap-2 text-slate-700">
                        <input type="checkbox" wire:model.live="show_appointments" />
                        Show appointments
                    </label>
                    <label class="flex items-center gap-2 text-slate-700">
                        <input type="checkbox" wire:model.live="show_demos" />
                        Show demos
                    </label>
                    <label class="flex items-center gap-2 text-slate-700">
                        <input type="checkbox" wire:model.live="show_meetings" />
                        Show meetings
                    </label>
                    @if ($personalOnly)
                        <p class="rounded-xl bg-teal-50 px-3 py-2 text-xs font-semibold text-teal-800">
                            Showing only your schedule.
                        </p>
                    @endif
                </div>
            </x-crm.calendar-panel>

            @unless ($personalOnly)
                <livewire:crm.consultant-performance-panel />
            @endunless
        </div>

        <div class="space-y-4 xl:col-span-3">
            <x-crm.calendar-panel class="mb-0" tone="teal">
                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            class="inline-flex items-center justify-center rounded-full border border-teal-200 bg-white px-3 py-2 text-teal-700 shadow-sm transition hover:border-teal-300 hover:bg-teal-50"
                            type="button"
                            wire:click="previous"
                            aria-label="Previous period"
                        >
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                            </svg>
                        </button>
                        <button class="rounded-full border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-600" type="button" wire:click="goToday">Today</button>
                        <button
                            class="inline-flex items-center justify-center rounded-full border border-teal-200 bg-white px-3 py-2 text-teal-700 shadow-sm transition hover:border-teal-300 hover:bg-teal-50"
                            type="button"
                            wire:click="next"
                            aria-label="Next period"
                        >
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>
                        <p class="ml-1 text-sm font-semibold text-slate-700">
                            @if ($view === 'day')
                                {{ $focus->format('l, F j, Y') }}
                            @elseif ($view === 'year')
                                {{ $focus->format('Y') }}
                            @else
                                {{ $focus->format('F Y') }}
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['year' => 'Year', 'month' => 'Month', 'week' => 'Week', 'day' => 'Day', 'agenda' => 'Agenda'] as $key => $label)
                            <button
                                type="button"
                                class="rounded-full px-4 py-2 text-sm font-semibold transition {{ $view === $key ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                                wire:click="setView('{{ $key }}')"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </x-crm.calendar-panel>

            <livewire:crm.calendar.calendar-grid
                :view="$view"
                :focus-date="$focusDate"
                :filters="$filters"
                :can-manage="$canManage"
                wire:key="calendar-grid-{{ $view }}-{{ $focusDate }}-{{ md5(json_encode($filters)) }}"
            />

            @unless ($personalOnly)
                <livewire:crm.consultant-performance-summary />
            @endunless
        </div>
    </div>

    <livewire:crm.calendar.calendar-event-modal />
    <livewire:crm.calendar.calendar-detail-panel />
</div>
