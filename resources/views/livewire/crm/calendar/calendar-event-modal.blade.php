<div>
@if ($show)
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
        x-data
        x-on:keydown.escape.window="$wire.close()"
    >
        <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-teal-200/80 bg-gradient-to-br from-teal-50 via-white to-emerald-50/70 p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between border-b border-teal-100/80 pb-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">{{ $editingEventId ? 'Edit Event' : 'Schedule Event' }}</h2>
                    @if (! $usesCrmFields)
                        <p class="mt-1 text-xs font-semibold text-teal-700">Internal event — CRM contact fields are hidden.</p>
                    @endif
                </div>
                <button class="text-slate-500 hover:text-slate-800" type="button" wire:click="close">✕</button>
            </div>

            <form class="space-y-4" wire:submit="save">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Title</label>
                        <input class="w-full rounded-xl border-slate-200" type="text" wire:model="title" />
                        @error('title') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    @if ($userCalendars->isNotEmpty())
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Calendar</label>
                            <select class="w-full rounded-xl border-slate-200" wire:model.live="user_calendar_id">
                                @foreach ($userCalendars as $calendar)
                                    <option value="{{ $calendar->id }}">{{ $calendar->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div @class(['md:col-span-2' => ! $userCalendars->isNotEmpty()])>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Event type</label>
                        <select class="w-full rounded-xl border-slate-200" wire:model.live="calendar_event_type_id">
                            @foreach ($eventTypesByCategory as $category => $types)
                                <optgroup label="{{ $category }}">
                                    @foreach ($types as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    @if ($usesCrmFields && $canAssign)
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Assigned to</label>
                            <select class="w-full rounded-xl border-slate-200" wire:model="user_id">
                                @foreach ($assignableUsers as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if ($usesCrmFields)
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Related record</label>
                            <select class="w-full rounded-xl border-slate-200" wire:model="related_key">
                                <option value="">— None —</option>
                                @foreach ($contacts as $contact)
                                    <option value="{{ $contact->getMorphClass() }}:{{ $contact->id }}">{{ $contact->fullName() }} ({{ $contact->lifecycleSlug()->value }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="md:col-span-2">
                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <input type="checkbox" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500" wire:model.live="is_all_day" />
                            All-day event
                        </label>
                        <p class="mt-1 text-xs text-slate-500">Hide times and allow the event to span one or more full days.</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">{{ $is_all_day ? 'Start date' : 'Starts' }}</label>
                        <input
                            class="w-full rounded-xl border-slate-200"
                            type="{{ $is_all_day ? 'date' : 'datetime-local' }}"
                            wire:model="start_at"
                        />
                        @error('start_at') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">{{ $is_all_day ? 'End date' : 'Ends' }}</label>
                        <input
                            class="w-full rounded-xl border-slate-200"
                            type="{{ $is_all_day ? 'date' : 'datetime-local' }}"
                            wire:model="end_at"
                        />
                        @error('end_at') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    @if ($editingEventId && $recurrenceSummary)
                        <div class="md:col-span-2 rounded-xl border border-teal-200 bg-teal-50/70 px-3 py-2 text-xs font-medium text-teal-800">
                            {{ $recurrenceSummary }}
                        </div>
                    @elseif (! $editingEventId)
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Repeat</label>
                            <select class="w-full rounded-xl border-slate-200" wire:model.live="recurrence">
                                @foreach ($recurrenceOptions as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            @error('recurrence') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        @if ($recurrence !== 'none')
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Occurrences</label>
                                <select class="w-full rounded-xl border-slate-200" wire:model="recurrence_count">
                                    @foreach ($recurrenceCounts as $count)
                                        <option value="{{ $count }}">{{ $count }} times</option>
                                    @endforeach
                                </select>
                                @error('recurrence_count') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    @endif

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Priority</label>
                        <select class="w-full rounded-xl border-slate-200" wire:model="priority">
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->value }}">{{ $priority->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Status</label>
                        <select class="w-full rounded-xl border-slate-200" wire:model="status">
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}">{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div @class(['md:col-span-2' => ! $usesCrmFields])>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Location</label>
                        <input class="w-full rounded-xl border-slate-200" type="text" wire:model="location" />
                    </div>
                    @if ($usesCrmFields)
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Meeting link</label>
                            <input class="w-full rounded-xl border-slate-200" type="url" wire:model="meeting_link" />
                        </div>
                    @endif
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Description</label>
                        <textarea class="w-full rounded-xl border-slate-200" rows="3" wire:model="description"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Reminders</label>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($reminderPresets as $minutes => $label)
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" value="{{ $minutes }}" wire:model="reminder_minutes" />
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <button class="rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-600" type="button" wire:click="close">Cancel</button>
                    <button class="rounded-full bg-teal-600 px-5 py-2 text-sm font-semibold text-white hover:bg-teal-700" type="submit">Save Event</button>
                </div>
            </form>
        </div>
    </div>
@endif
</div>
