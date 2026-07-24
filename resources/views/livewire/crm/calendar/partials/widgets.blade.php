@php
    $addBtn = 'inline-flex size-7 items-center justify-center rounded-lg border border-slate-200/80 bg-white/70 text-slate-700 transition hover:bg-white hover:text-teal-800';
@endphp

<x-crm.calendar-panel title="Upcoming Shows/Demos" tone="violet" panel-key="upcoming-shows" :count="$upcoming->count()">
    <x-slot:actions>
        @if ($canManage ?? false)
            <button type="button" class="{{ $addBtn }}" wire:click="openCreateModal('show')" title="Add show or demo" aria-label="Add show or demo">
                <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            </button>
        @endif
    </x-slot:actions>
    <ul class="mt-3 space-y-2 text-sm">
        @forelse ($upcoming as $entry)
            <li class="flex items-start gap-2 rounded-xl border border-white/60 bg-white/50 p-2 backdrop-blur-sm">
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-slate-900">{{ $entry->title }}</p>
                    <p class="text-xs text-slate-500">{{ $entry->type_name }} · {{ $entry->start_at->format('M j · g:i A') }}</p>
                </div>
                @include('livewire.crm.calendar.partials.widget-view-button', ['kind' => $entry->kind, 'id' => $entry->id])
            </li>
        @empty
            <li class="text-slate-500">No upcoming shows or demos.</li>
        @endforelse
    </ul>
</x-crm.calendar-panel>

<x-crm.calendar-panel title="Call Lists Today" tone="blue" panel-key="call-lists" :count="$callListsToday->count()">
    <x-slot:actions>
        @if ($canManage ?? false)
            <button type="button" class="{{ $addBtn }}" wire:click="openCreateModal('call')" title="Add phone call" aria-label="Add phone call">
                <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            </button>
        @endif
    </x-slot:actions>
    <ul class="mt-3 space-y-2 text-sm">
        @forelse ($callListsToday as $entry)
            @php
                $isDone = ($entry->status ?? null) === 'completed';
                $isPendingResult = $resultsEventId === $entry->id;
            @endphp
            <li @class([
                'flex items-start gap-2 rounded-xl border border-white/60 p-2 backdrop-blur-sm',
                'bg-emerald-50/70' => $isDone,
                'bg-white/50' => ! $isDone,
            ])>
                @if ($canManage ?? false)
                    <input
                        type="checkbox"
                        class="mt-0.5 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                        @checked($isDone || $isPendingResult)
                        @disabled($isDone)
                        wire:click.prevent="beginCompleteCall({{ $entry->id }})"
                    >
                @endif
                <div class="min-w-0 flex-1">
                    <p @class([
                        'font-semibold text-slate-900',
                        'line-through opacity-70' => $isDone || $isPendingResult,
                    ])>
                        {{ $entry->contact_name }}
                    </p>
                    <p class="text-xs text-slate-500">
                        @if (filled($entry->phone))
                            {{ $entry->phone }} ·
                        @endif
                        {{ $entry->start_at->format('g:i A') }}
                        @if (filled($entry->reason))
                            · {{ $entry->reason }}
                        @elseif (filled($entry->type_name))
                            · {{ $entry->type_name }}
                        @endif
                    </p>
                </div>
                @include('livewire.crm.calendar.partials.widget-view-button', ['kind' => $entry->kind, 'id' => $entry->id])
            </li>
        @empty
            <li class="text-slate-500">No phone calls scheduled for today.</li>
        @endforelse
    </ul>
</x-crm.calendar-panel>

<x-crm.calendar-panel title="Overdue Follow-Ups" tone="rose" panel-key="overdue-followups" :count="$overdueFollowUps->count()">
    <x-slot:actions>
        @if ($canManage ?? false)
            <button type="button" class="{{ $addBtn }}" wire:click="openCreateModal('followup')" title="Add follow-up" aria-label="Add follow-up">
                <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            </button>
        @endif
    </x-slot:actions>
    <ul class="mt-3 space-y-2 text-sm">
        @forelse ($overdueFollowUps as $entry)
            @php
                $isPhoneCall = $entry->kind === 'phone_call';
                $isDone = $isPhoneCall && ($entry->status ?? null) === 'completed';
                $isPendingResult = $isPhoneCall && $resultsEventId === $entry->id;
                $detailKind = $isPhoneCall ? 'event' : $entry->kind;
            @endphp
            <li @class([
                'flex items-start gap-2 rounded-xl border border-white/60 p-2 backdrop-blur-sm',
                'bg-emerald-50/70' => $isDone,
                'bg-white/50' => ! $isDone,
            ])>
                @if ($isPhoneCall && ($canManage ?? false))
                    <input
                        type="checkbox"
                        class="mt-0.5 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                        @checked($isDone || $isPendingResult)
                        @disabled($isDone)
                        wire:click.prevent="beginCompleteCall({{ $entry->id }})"
                    >
                @endif
                <div class="min-w-0 flex-1">
                    <p @class([
                        'font-medium text-rose-700',
                        'line-through opacity-70' => $isDone || $isPendingResult,
                    ])>
                        {{ $entry->contact_name }}
                    </p>
                    <p class="text-xs text-slate-500">
                        @if ($isPhoneCall && filled($entry->phone))
                            {{ $entry->phone }} ·
                        @endif
                        Due {{ $entry->due_at?->format($isPhoneCall ? 'M j · g:i A' : 'M j') }}
                        @if ($isPhoneCall && filled($entry->reason))
                            · {{ $entry->reason }}
                        @endif
                    </p>
                </div>
                @include('livewire.crm.calendar.partials.widget-view-button', ['kind' => $detailKind, 'id' => $entry->id])
            </li>
        @empty
            <li class="text-slate-500">No overdue follow-ups.</li>
        @endforelse
    </ul>
</x-crm.calendar-panel>

<x-crm.calendar-panel title="Tasks Due Today" tone="amber" panel-key="tasks-due" :count="$tasksDueToday->count()">
    <x-slot:actions>
        @if ($canManage ?? false)
            <button type="button" class="{{ $addBtn }}" wire:click="openCreateModal('task')" title="Add task" aria-label="Add task">
                <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            </button>
        @endif
    </x-slot:actions>
    <ul class="mt-3 space-y-2 text-sm">
        @forelse ($tasksDueToday as $task)
            <li class="flex items-center gap-2 rounded-xl border border-white/60 bg-white/50 p-2 backdrop-blur-sm">
                <span class="min-w-0 flex-1 font-medium text-slate-800">{{ $task->title }}</span>
                <div class="flex shrink-0 items-center gap-1">
                    @include('livewire.crm.calendar.partials.widget-view-button', ['kind' => 'task', 'id' => $task->id])
                    @if ($canManage ?? true)
                        <button class="rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-700 hover:bg-emerald-100" type="button" wire:click="completeTask({{ $task->id }})">Done</button>
                    @endif
                </div>
            </li>
        @empty
            <li class="text-slate-500">No tasks due today.</li>
        @endforelse
    </ul>
</x-crm.calendar-panel>
