<x-crm.calendar-panel title="Upcoming Shows/Demos" tone="violet">
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

<x-crm.calendar-panel title="Call Lists Today" tone="blue">
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

<x-crm.calendar-panel title="Overdue Follow-Ups" tone="rose">
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

<x-crm.calendar-panel title="Tasks Due Today" tone="amber">
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
