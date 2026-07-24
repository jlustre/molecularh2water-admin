<div class="space-y-4">
@include('livewire.crm.calendar.partials.widgets', [
    'upcoming' => $upcoming,
    'callListsToday' => $callListsToday,
    'overdueFollowUps' => $overdueFollowUps,
    'tasksDueToday' => $tasksDueToday,
    'typeColors' => $typeColors,
    'canManage' => $canManage,
    'resultsEventId' => $resultsEventId,
])

@if ($showResults)
    <div class="fixed inset-0 z-[60] flex items-center justify-center px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="calendar-phone-call-results-title">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="cancelCallResults"></div>
        <div class="relative w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div class="border-b border-slate-100 px-5 py-4">
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-700">Call Results</p>
                <h3 id="calendar-phone-call-results-title" class="mt-1 text-lg font-black text-slate-950">{{ $resultsContactLabel }}</h3>
                <p class="mt-1 text-xs text-slate-500">Saved to your calendar and CRM activity log.</p>
            </div>
            <form class="space-y-4 px-5 py-5" wire:submit="saveCallResults">
                <div>
                    <label for="calendar-phone-call-result" class="mb-1 block text-sm font-semibold text-slate-700">Call result</label>
                    <select
                        id="calendar-phone-call-result"
                        wire:model.live="call_result"
                        class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        required
                    >
                        <option value="">Select a result…</option>
                        @foreach ($resultOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('call_result') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="calendar-phone-call-result-comments" class="mb-1 block text-sm font-semibold text-slate-700">
                        Comments <span class="font-normal text-slate-400">(optional)</span>
                    </label>
                    <textarea
                        id="calendar-phone-call-result-comments"
                        wire:model="result_comments"
                        rows="3"
                        placeholder="Notes from the conversation…"
                        class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                    ></textarea>
                    @error('result_comments') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input
                            type="checkbox"
                            wire:model.live="reschedule_enabled"
                            class="mt-0.5 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                        >
                        <span>
                            <span class="block text-sm font-semibold text-slate-900">Schedule follow-up call</span>
                            <span class="mt-0.5 block text-xs text-slate-500">Adds a new call to your list and CRM calendar.</span>
                        </span>
                    </label>

                    @if ($reschedule_enabled)
                        <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                            <div>
                                <label for="calendar-phone-call-reschedule-when" class="mb-1 block text-sm font-semibold text-slate-700">When</label>
                                <select id="calendar-phone-call-reschedule-when" wire:model.live="reschedule_when" class="block w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                    <option value="in_15">In 15 minutes</option>
                                    <option value="in_30">In 30 minutes</option>
                                    <option value="in_60">In 1 hour</option>
                                    <option value="today_14">Today at 2:00 PM</option>
                                    <option value="today_16">Today at 4:00 PM</option>
                                    <option value="tomorrow_10">Tomorrow at 10:00 AM</option>
                                    <option value="custom">Pick date & time</option>
                                </select>
                                @error('reschedule_when') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            @if ($reschedule_when === 'custom')
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label for="calendar-phone-call-reschedule-date" class="mb-1 block text-sm font-semibold text-slate-700">Date</label>
                                        <input
                                            id="calendar-phone-call-reschedule-date"
                                            type="date"
                                            wire:model="reschedule_date"
                                            class="block w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                            required
                                        />
                                        @error('reschedule_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="calendar-phone-call-reschedule-time" class="mb-1 block text-sm font-semibold text-slate-700">Time</label>
                                        <input
                                            id="calendar-phone-call-reschedule-time"
                                            type="time"
                                            wire:model="reschedule_time"
                                            class="block w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                            required
                                        />
                                        @error('reschedule_time') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            @endif
                            <div>
                                <label for="calendar-phone-call-reschedule-reason" class="mb-1 block text-sm font-semibold text-slate-700">Reason for follow-up</label>
                                <select id="calendar-phone-call-reschedule-reason" wire:model.live="reschedule_reason" class="block w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                    <option value="">Select a reason…</option>
                                    @foreach ($rescheduleReasonOptions as $reason)
                                        <option value="{{ $reason['value'] }}">{{ $reason['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('reschedule_reason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="calendar-phone-call-reschedule-notes" class="mb-1 block text-sm font-semibold text-slate-700">
                                    Notes
                                    @if ($reschedule_reason === 'other')
                                        <span class="font-normal text-amber-700">(required for Other)</span>
                                    @else
                                        <span class="font-normal text-slate-400">(optional)</span>
                                    @endif
                                </label>
                                <textarea
                                    id="calendar-phone-call-reschedule-notes"
                                    wire:model="reschedule_notes"
                                    rows="2"
                                    class="block w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                ></textarea>
                                @error('reschedule_notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                    <button type="button" wire:click="cancelCallResults" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">
                        Cancel
                    </button>
                    <x-primary-button type="submit">
                        Save results
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
@endif

@if ($createModal)
    @php
        $modalTitles = [
            'show' => 'Add Show / Demo',
            'call' => 'Add Phone Call',
            'followup' => 'Add Follow-Up Call',
            'task' => 'Add Task',
        ];
    @endphp
    <div class="fixed inset-0 z-[60] flex items-center justify-center px-4 py-6" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="closeCreateModal"></div>
        <div class="relative w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div class="border-b border-slate-100 px-5 py-4">
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-700">Calendar Widget</p>
                <h3 class="mt-1 text-lg font-black text-slate-950">{{ $modalTitles[$createModal] ?? 'Add record' }}</h3>
            </div>
            <form class="space-y-4 px-5 py-5" wire:submit="saveCreateModal">
                @if ($createModal === 'show')
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700" for="widget-create-title">Title</label>
                        <input id="widget-create-title" type="text" wire:model="create_title" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" required>
                        @error('create_title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700" for="widget-create-type">Type</label>
                        <select id="widget-create-type" wire:model="create_type_id" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" required>
                            <option value="">Select type…</option>
                            @foreach ($showDemoTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                        @error('create_type_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700" for="widget-create-start">Starts</label>
                        <input id="widget-create-start" type="datetime-local" wire:model="create_start_at" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" required>
                        @error('create_start_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700" for="widget-create-location">Location <span class="font-normal text-slate-400">(optional)</span></label>
                        <input id="widget-create-location" type="text" wire:model="create_location" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    </div>
                @elseif (in_array($createModal, ['call', 'followup'], true))
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700" for="widget-create-contact">Contact name</label>
                        <input id="widget-create-contact" type="text" wire:model="create_contact_name" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" required>
                        @error('create_contact_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700" for="widget-create-phone">Phone <span class="font-normal text-slate-400">(optional)</span></label>
                        <input id="widget-create-phone" type="text" wire:model="create_phone" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700" for="widget-create-reason">Reason</label>
                        <select id="widget-create-reason" wire:model="create_reason" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" required>
                            @foreach ($callReasonOptions as $reason)
                                <option value="{{ $reason['value'] }}">{{ $reason['label'] }}</option>
                            @endforeach
                        </select>
                        @error('create_reason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700" for="widget-create-call-when">
                            {{ $createModal === 'followup' ? 'Due at' : 'Call time' }}
                        </label>
                        <input id="widget-create-call-when" type="datetime-local" wire:model="create_start_at" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" required>
                        @error('create_start_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        @if ($createModal === 'followup')
                            <p class="mt-1 text-xs text-slate-500">Use a past time to place it in Overdue Follow-Ups.</p>
                        @endif
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700" for="widget-create-notes">Notes <span class="font-normal text-slate-400">(optional)</span></label>
                        <textarea id="widget-create-notes" rows="2" wire:model="create_notes" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"></textarea>
                    </div>
                @elseif ($createModal === 'task')
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700" for="widget-create-task-title">Task title</label>
                        <input id="widget-create-task-title" type="text" wire:model="create_task_title" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" required>
                        @error('create_task_title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700" for="widget-create-due">Due</label>
                        <input id="widget-create-due" type="datetime-local" wire:model="create_due_at" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" required>
                        @error('create_due_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700" for="widget-create-task-notes">Notes <span class="font-normal text-slate-400">(optional)</span></label>
                        <textarea id="widget-create-task-notes" rows="2" wire:model="create_notes" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"></textarea>
                    </div>
                @endif

                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                    <button type="button" wire:click="closeCreateModal" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">
                        Cancel
                    </button>
                    <x-primary-button type="submit">
                        Save
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
@endif
</div>
