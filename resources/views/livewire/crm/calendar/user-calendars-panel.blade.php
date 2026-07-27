<x-crm.calendar-panel title="My Calendars" tone="teal">
    <div class="mt-3 space-y-3 text-sm">
        <p class="text-xs text-slate-500">
            Toggle calendars to show or hide their events on the grid. Each calendar has its own theme color.
        </p>

        <div class="space-y-2">
            @forelse ($calendars as $calendar)
                @php
                    $owned = (int) $calendar->user_id === (int) auth()->id();
                    $swatch = $colorClasses[$calendar->color] ?? $colorClasses['teal'] ?? 'bg-teal-100 text-teal-800 border-teal-200';
                @endphp
                <div class="rounded-xl border border-slate-100 bg-white px-3 py-2.5" wire:key="user-calendar-{{ $calendar->id }}">
                    @if ($editingCalendarId === $calendar->id)
                        <div class="space-y-2">
                            <input class="w-full rounded-lg border-slate-200 text-sm" type="text" wire:model="editName" />
                            <select class="w-full rounded-lg border-slate-200 text-sm" wire:model="editColor">
                                @foreach ($colors as $color)
                                    <option value="{{ $color }}">{{ ucfirst($color) }}</option>
                                @endforeach
                            </select>
                            <div class="flex gap-2">
                                <button type="button" class="rounded-full bg-teal-600 px-3 py-1 text-xs font-bold text-white" wire:click="saveEdit">Save</button>
                                <button type="button" class="rounded-full border border-slate-200 px-3 py-1 text-xs font-bold text-slate-600" wire:click="cancelEdit">Cancel</button>
                            </div>
                        </div>
                    @else
                        <div class="flex items-start gap-2">
                            <label class="mt-0.5 flex min-w-0 flex-1 cursor-pointer items-start gap-2">
                                <input
                                    type="checkbox"
                                    class="mt-1 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                                    wire:model.live="calendarVisibility.{{ $calendar->id }}"
                                />
                                <span class="min-w-0">
                                    <span class="flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex rounded-full border px-2 py-0.5 text-[11px] font-bold {{ $swatch }}">
                                            {{ $calendar->name }}
                                        </span>
                                        @unless ($owned)
                                            <span class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Shared</span>
                                        @endunless
                                    </span>
                                    @if (! $owned && $calendar->owner)
                                        <span class="mt-0.5 block text-[11px] text-slate-400">from {{ $calendar->owner->name }}</span>
                                    @endif
                                </span>
                            </label>
                            @if ($canManage && $owned && ! $calendar->isHolidayKind())
                                <div class="flex shrink-0 gap-1">
                                    <button type="button" class="text-[11px] font-bold text-teal-700 hover:text-teal-900" wire:click="startEdit({{ $calendar->id }})">Edit</button>
                                    <button type="button" class="text-[11px] font-bold text-teal-700 hover:text-teal-900" wire:click="openShare({{ $calendar->id }})">Share</button>
                                    @if (! in_array($calendar->kind, ['personal', 'work'], true) && ! $calendar->is_default)
                                        <button
                                            type="button"
                                            class="text-[11px] font-bold text-rose-600 hover:text-rose-800"
                                            wire:click="deleteCalendar({{ $calendar->id }})"
                                            wire:confirm="Delete this calendar and unlink its events?"
                                        >Delete</button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <p class="rounded-xl border border-dashed border-slate-200 px-3 py-4 text-center text-xs font-semibold text-slate-500">
                    No calendars yet.
                </p>
            @endforelse
        </div>

        @if ($sharingCalendar)
            <div class="rounded-xl border border-teal-100 bg-teal-50/60 p-3">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-700">Share {{ $sharingCalendar->name }}</p>
                    <button type="button" class="text-[11px] font-bold text-slate-500" wire:click="closeShare">Close</button>
                </div>
                <div class="mt-2 flex gap-2">
                    <select class="w-full rounded-lg border-slate-200 text-sm" wire:model="shareUserId">
                        <option value="">Select member…</option>
                        @foreach ($shareTargets as $target)
                            <option value="{{ $target->id }}">{{ $target->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="rounded-full bg-teal-600 px-3 py-1.5 text-xs font-bold text-white" wire:click="shareCalendar">Share</button>
                </div>
                @if ($sharingCalendar->sharedWithUsers->isNotEmpty())
                    <ul class="mt-2 space-y-1">
                        @foreach ($sharingCalendar->sharedWithUsers as $sharedUser)
                            <li class="flex items-center justify-between gap-2 text-xs text-slate-700">
                                <span>{{ $sharedUser->name }}</span>
                                <button type="button" class="font-bold text-rose-600" wire:click="unshareCalendar({{ $sharedUser->id }})">Remove</button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        @if ($canManage)
            <div class="space-y-2 border-t border-slate-100 pt-3">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Add calendar</p>
                <input class="w-full rounded-lg border-slate-200 text-sm" type="text" placeholder="Calendar name" wire:model="newName" />
                <select class="w-full rounded-lg border-slate-200 text-sm" wire:model="newColor">
                    @foreach ($colors as $color)
                        <option value="{{ $color }}">{{ ucfirst($color) }} theme</option>
                    @endforeach
                </select>
                <button type="button" class="w-full rounded-full bg-teal-600 px-3 py-2 text-xs font-bold text-white hover:bg-teal-700" wire:click="createCalendar">
                    Create calendar
                </button>
                <div class="flex flex-wrap gap-2 pt-1">
                    @unless ($hasUsHolidays)
                        <button type="button" class="rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 text-[11px] font-bold text-rose-700" wire:click="addHolidayCalendar('us_holidays')">
                            + US Holidays
                        </button>
                    @endunless
                    @unless ($hasCaHolidays)
                        <button type="button" class="rounded-full border border-red-200 bg-red-50 px-3 py-1.5 text-[11px] font-bold text-red-700" wire:click="addHolidayCalendar('ca_holidays')">
                            + Canadian Holidays
                        </button>
                    @endunless
                </div>
            </div>
        @endif
    </div>
</x-crm.calendar-panel>
