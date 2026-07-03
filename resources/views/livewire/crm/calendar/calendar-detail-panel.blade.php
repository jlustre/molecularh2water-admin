<div>

@if ($show)

    <div

        class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/50 p-4"

        role="dialog"

        aria-modal="true"

        x-data

        x-on:keydown.escape.window="$wire.close()"

    >

        <div class="fixed inset-0" wire:click="close"></div>

        <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-teal-200/80 bg-gradient-to-br from-teal-50 via-white to-emerald-50/70 shadow-2xl">

            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-teal-100/80 bg-gradient-to-r from-teal-50 to-emerald-50/60 px-5 py-4">

                <div>

                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">

                        @switch($resolvedKind)

                            @case('phone_call') Phone Call @break

                            @case('task') Task @break

                            @case('lead') Follow-up @break

                            @case('demonstration') Demo / Show @break

                            @default Event Details

                        @endswitch

                    </p>

                </div>

                <button class="text-slate-500 hover:text-slate-800" type="button" wire:click="close">✕</button>

            </div>



            <div class="space-y-5 px-5 py-5">

                @if ($resolvedKind === 'phone_call' && $selectedEvent)

                    <div>

                        <h3 class="text-xl font-bold text-slate-900">{{ app(\App\Services\Portal\PhoneCallService::class)->contactLabel($selectedEvent) }}</h3>

                        <p class="mt-1 text-sm text-slate-600">{{ $selectedEvent->start_at?->format('l, F j, Y g:i A') }}</p>

                        @if ($phone = app(\App\Services\Portal\PhoneCallService::class)->displayPhone($selectedEvent))

                            <p class="mt-2 text-sm text-slate-700"><span class="font-semibold">Phone:</span> {{ $phone }}</p>

                        @endif

                        @if ($reason = \App\Support\Portal\PhoneCallReasons::label((string) ($selectedEvent->metadata['phone_call_reason'] ?? '')))

                            <p class="mt-1 text-sm text-slate-500">{{ $reason }}</p>

                        @endif

                        @if ($contact = $selectedEvent->crmContact())

                            <a class="mt-3 inline-flex text-sm font-semibold text-teal-700" href="{{ match ($contact->getMorphClass()) {
                                'prospect' => route(\App\Support\Crm\CrmRoutes::name('prospects.show'), $contact),
                                'customer' => route(\App\Support\Crm\CrmRoutes::name('customers.show'), $contact),
                                'recruit' => route(\App\Support\Crm\CrmRoutes::name('recruits.show'), $contact),
                                default => route(\App\Support\Crm\CrmRoutes::name('leads.show'), $contact),
                            } }}">View {{ $contact->lifecycleSlug()->value }}</a>

                        @endif

                    </div>



                    @if ($canManageCalendar && $selectedEvent->status?->value !== 'completed')

                        <form class="space-y-3 rounded-xl border border-slate-200 bg-white/70 p-4" wire:submit="save">

                            <p class="text-sm font-semibold text-slate-800">Reschedule call</p>

                            <div>

                                <label class="mb-1 block text-xs font-semibold text-slate-600">When</label>

                                <select wire:model.live="phone_call_when" class="w-full rounded-xl border-slate-200 text-sm">

                                    @foreach ($phoneCallWhenOptions as $value => $label)

                                        <option value="{{ $value }}">{{ $label }}</option>

                                    @endforeach

                                </select>

                                @error('phone_call_when') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror

                            </div>

                            @if ($phone_call_when === 'custom')

                                <div class="grid gap-3 sm:grid-cols-2">

                                    <div>

                                        <label class="mb-1 block text-xs font-semibold text-slate-600">Date</label>

                                        <input wire:model="phone_call_date" type="date" class="w-full rounded-xl border-slate-200 text-sm" required />

                                        @error('phone_call_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror

                                    </div>

                                    <div>

                                        <label class="mb-1 block text-xs font-semibold text-slate-600">Time</label>

                                        <input wire:model="phone_call_time" type="time" class="w-full rounded-xl border-slate-200 text-sm" required />

                                        @error('phone_call_time') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror

                                    </div>

                                </div>

                            @endif

                            <div>

                                <label class="mb-1 block text-xs font-semibold text-slate-600">Phone</label>

                                <input wire:model="phone_number" type="text" class="w-full rounded-xl border-slate-200 text-sm" />

                                @error('phone_number') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror

                            </div>

                            <div>

                                <label class="mb-1 block text-xs font-semibold text-slate-600">Reason</label>

                                <select wire:model.live="phone_call_reason" class="w-full rounded-xl border-slate-200 text-sm">

                                    @foreach ($phoneReasonOptions as $reason)

                                        <option value="{{ $reason['value'] }}">{{ $reason['label'] }}</option>

                                    @endforeach

                                </select>

                                @error('phone_call_reason') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror

                            </div>

                            <div>

                                <label class="mb-1 block text-xs font-semibold text-slate-600">Notes</label>

                                <textarea wire:model="phone_call_notes" rows="2" class="w-full rounded-xl border-slate-200 text-sm"></textarea>

                                @error('phone_call_notes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror

                            </div>

                            <div class="flex justify-end gap-2 pt-2">

                                <button type="button" wire:click="close" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>

                                <x-primary-button type="submit">Save changes</x-primary-button>

                            </div>

                        </form>

                    @endif



                @elseif ($resolvedKind === 'task' && $selectedTask)

                    <div>

                        <h3 class="text-xl font-bold text-slate-900">{{ $selectedTask->title }}</h3>

                        <p class="mt-1 text-sm text-slate-600">Due {{ $selectedTask->due_at?->format('l, F j, Y g:i A') ?? '—' }}</p>

                        @if ($selectedTask->lead)

                            <a class="mt-2 inline-flex text-sm font-semibold text-teal-700" href="{{ route(\App\Support\Crm\CrmRoutes::name('leads.show'), $selectedTask->lead) }}">{{ $selectedTask->lead->fullName() }}</a>

                        @endif

                        @if ($selectedTask->description)

                            <p class="mt-3 text-sm text-slate-700">{{ $selectedTask->description }}</p>

                        @endif

                    </div>



                    @if ($canManageTasks)

                        <form class="space-y-3 rounded-xl border border-slate-200 bg-white/70 p-4" wire:submit="save">

                            <p class="text-sm font-semibold text-slate-800">Edit task</p>

                            <div>

                                <label class="mb-1 block text-xs font-semibold text-slate-600">Title</label>

                                <input wire:model="task_title" type="text" class="w-full rounded-xl border-slate-200 text-sm" />

                                @error('task_title') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror

                            </div>

                            <div>

                                <label class="mb-1 block text-xs font-semibold text-slate-600">Due</label>

                                <input wire:model="task_due_at" type="datetime-local" class="w-full rounded-xl border-slate-200 text-sm" />

                                @error('task_due_at') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror

                            </div>

                            <div>

                                <label class="mb-1 block text-xs font-semibold text-slate-600">Priority</label>

                                <select wire:model="task_priority" class="w-full rounded-xl border-slate-200 text-sm">

                                    @foreach ($taskPriorities as $priority)

                                        <option value="{{ $priority->value }}">{{ $priority->label() }}</option>

                                    @endforeach

                                </select>

                            </div>

                            <div>

                                <label class="mb-1 block text-xs font-semibold text-slate-600">Description</label>

                                <textarea wire:model="task_description" rows="2" class="w-full rounded-xl border-slate-200 text-sm"></textarea>

                            </div>

                            <div class="flex justify-end gap-2 pt-2">

                                <button type="button" wire:click="close" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>

                                <x-primary-button type="submit">Save changes</x-primary-button>

                            </div>

                        </form>

                    @endif



                @elseif ($resolvedKind === 'lead' && $selectedLead)

                    <div>

                        <h3 class="text-xl font-bold text-slate-900">{{ $selectedLead->fullName() }}</h3>

                        <p class="mt-1 text-sm text-rose-600">Overdue follow-up · {{ $selectedLead->next_follow_up_at?->format('l, F j, Y g:i A') }}</p>

                        @if ($selectedLead->phone)

                            <p class="mt-2 text-sm text-slate-700"><span class="font-semibold">Phone:</span> {{ $selectedLead->phone }}</p>

                        @endif

                        <a class="mt-3 inline-flex text-sm font-semibold text-teal-700" href="{{ route(\App\Support\Crm\CrmRoutes::name('leads.show'), $selectedLead) }}">Open lead profile</a>

                    </div>



                    @if ($canManageLeads)

                        <form class="space-y-3 rounded-xl border border-slate-200 bg-white/70 p-4" wire:submit="save">

                            <p class="text-sm font-semibold text-slate-800">Reschedule follow-up</p>

                            <div>

                                <label class="mb-1 block text-xs font-semibold text-slate-600">Next follow-up</label>

                                <input wire:model="lead_follow_up_at" type="datetime-local" class="w-full rounded-xl border-slate-200 text-sm" />

                                @error('lead_follow_up_at') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror

                            </div>

                            <div class="flex justify-end gap-2 pt-2">

                                <button type="button" wire:click="close" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>

                                <x-primary-button type="submit">Save changes</x-primary-button>

                            </div>

                        </form>

                    @endif



                @elseif ($resolvedKind === 'demonstration' && $selectedDemonstration)

                    <div>

                        <p class="text-xs font-bold uppercase tracking-wide text-violet-600">{{ $selectedDemonstration->type?->label() }}</p>

                        <h3 class="mt-1 text-xl font-bold text-slate-900">{{ $selectedDemonstration->lead?->fullName() ?? 'Scheduled demo' }}</h3>

                        <p class="mt-1 text-sm text-slate-600">{{ $selectedDemonstration->scheduled_at?->format('l, F j, Y g:i A') }}</p>

                        @if ($selectedDemonstration->venue)

                            <p class="mt-2 text-sm text-slate-700"><span class="font-semibold">Location:</span> {{ $selectedDemonstration->venue }}</p>

                        @endif

                        @if ($selectedDemonstration->notes)

                            <p class="mt-3 text-sm text-slate-700">{{ $selectedDemonstration->notes }}</p>

                        @endif

                        @if ($contact = $selectedDemonstration->contact)

                            <a class="mt-3 inline-flex text-sm font-semibold text-teal-700" href="{{ match ($contact->getMorphClass()) {
                                'prospect' => route(\App\Support\Crm\CrmRoutes::name('prospects.show'), $contact),
                                'customer' => route(\App\Support\Crm\CrmRoutes::name('customers.show'), $contact),
                                'recruit' => route(\App\Support\Crm\CrmRoutes::name('recruits.show'), $contact),
                                default => route(\App\Support\Crm\CrmRoutes::name('leads.show'), $contact),
                            } }}">View {{ $contact->lifecycleSlug()->value }}</a>

                        @endif

                    </div>



                    @if ($canManageCalendar)

                        <form class="space-y-3 rounded-xl border border-slate-200 bg-white/70 p-4" wire:submit="save">

                            <p class="text-sm font-semibold text-slate-800">Reschedule demo</p>

                            <div>

                                <label class="mb-1 block text-xs font-semibold text-slate-600">Scheduled at</label>

                                <input wire:model="demo_scheduled_at" type="datetime-local" class="w-full rounded-xl border-slate-200 text-sm" />

                                @error('demo_scheduled_at') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror

                            </div>

                            <div>

                                <label class="mb-1 block text-xs font-semibold text-slate-600">Duration (minutes)</label>

                                <input wire:model="demo_duration_minutes" type="number" min="15" max="480" class="w-full rounded-xl border-slate-200 text-sm" />

                                @error('demo_duration_minutes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror

                            </div>

                            <div>

                                <label class="mb-1 block text-xs font-semibold text-slate-600">Location</label>

                                <input wire:model="demo_venue" type="text" class="w-full rounded-xl border-slate-200 text-sm" />

                            </div>

                            <div>

                                <label class="mb-1 block text-xs font-semibold text-slate-600">Notes</label>

                                <textarea wire:model="demo_notes" rows="2" class="w-full rounded-xl border-slate-200 text-sm"></textarea>

                            </div>

                            <div class="flex justify-end gap-2 pt-2">

                                <button type="button" wire:click="close" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>

                                <x-primary-button type="submit">Save changes</x-primary-button>

                            </div>

                        </form>

                    @endif

                    @if ($selectedDemonstration->calendarEvent)

                        <button class="w-full rounded-full border border-teal-200 bg-teal-50 py-2 text-sm font-semibold text-teal-800" type="button" wire:click="openDetails('event', {{ $selectedDemonstration->calendarEvent->id }})">

                            Open linked calendar event

                        </button>

                    @endif



                @elseif ($resolvedKind === 'event' && $selectedEvent)

                    <div>

                        <p class="text-xs font-bold uppercase tracking-wide text-teal-600">{{ $selectedEvent->type?->name }}</p>

                        <h3 class="mt-1 text-xl font-bold text-slate-900">{{ $selectedEvent->title }}</h3>

                        <p class="mt-2 text-sm text-slate-600">{{ $selectedEvent->start_at?->format('l, F j, Y g:i A') }}</p>

                        @if ($selectedEvent->end_at)

                            <p class="text-sm text-slate-500">Until {{ $selectedEvent->end_at->format('g:i A') }}</p>

                        @endif

                        @if ($selectedEvent->description)

                            <p class="mt-4 text-sm text-slate-700">{{ $selectedEvent->description }}</p>

                        @endif

                        @if ($selectedEvent->location)

                            <p class="mt-3 text-sm"><span class="font-semibold">Location:</span> {{ $selectedEvent->location }}</p>

                        @endif

                        @if ($selectedEvent->meeting_link)

                            <a class="mt-2 inline-block text-sm font-semibold text-teal-600" href="{{ $selectedEvent->meeting_link }}" target="_blank">Open meeting link</a>

                        @endif

                        @if ($contact = $selectedEvent->crmContact())

                            <a class="mt-4 inline-flex text-sm font-semibold text-teal-700" href="{{ match ($contact->getMorphClass()) {
                                'prospect' => route(\App\Support\Crm\CrmRoutes::name('prospects.show'), $contact),
                                'customer' => route(\App\Support\Crm\CrmRoutes::name('customers.show'), $contact),
                                'recruit' => route(\App\Support\Crm\CrmRoutes::name('recruits.show'), $contact),
                                default => route(\App\Support\Crm\CrmRoutes::name('leads.show'), $contact),
                            } }}">View {{ $contact->lifecycleSlug()->value }}</a>

                        @endif

                    </div>



                    @if ($canManageCalendar)

                        <div class="space-y-2 rounded-xl border border-slate-200 bg-white/70 p-4">

                            <button class="w-full rounded-full bg-teal-600 py-2 text-sm font-semibold text-white" type="button" wire:click="openEdit({{ $selectedEvent->id }})">Edit / Reschedule</button>

                            <textarea class="w-full rounded-xl border-slate-200 text-sm" placeholder="Completion notes" rows="2" wire:model="completion_notes"></textarea>

                            <button class="w-full rounded-full border border-emerald-200 bg-emerald-50 py-2 text-sm font-semibold text-emerald-800" type="button" wire:click="completeEvent({{ $selectedEvent->id }})">Mark Complete</button>

                            <button class="w-full rounded-full border border-amber-200 bg-amber-50 py-2 text-sm font-semibold text-amber-800" type="button" wire:click="cancelEvent({{ $selectedEvent->id }})">Cancel</button>

                            <button class="w-full rounded-full border border-rose-200 py-2 text-sm font-semibold text-rose-700" type="button" wire:click="deleteEvent({{ $selectedEvent->id }})">Delete</button>

                        </div>

                    @endif



                @else

                    <p class="text-sm text-slate-600">This item could not be loaded or is no longer available.</p>

                @endif

            </div>

        </div>

    </div>

@endif

</div>

