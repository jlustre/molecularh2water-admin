<div class="p-4 sm:p-6 lg:p-8">
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-600">CRM</p>
            <h1 class="mt-1 text-3xl font-bold text-slate-900">Appointments</h1>
            <p class="mt-1 text-sm text-slate-500">Schedule and manage meetings, calls, and presentations.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700" type="button" wire:click="previousMonth">←</button>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-800">{{ $monthLabel }}</span>
            <button class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700" type="button" wire:click="nextMonth">→</button>
            @if (auth()->user()?->hasPermission('appointments.manage'))
                <button
                    class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-teal-600 to-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm"
                    type="button"
                    wire:click="openForm"
                >
                    Schedule
                </button>
            @endif
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-7">
        @foreach (range(1, $monthStart->daysInMonth) as $day)
            @php
                $date = $monthStart->copy()->day($day);
                $dateKey = $date->format('Y-m-d');
                $dayAppointments = $appointments->get($dateKey, collect());
            @endphp
            <div class="min-h-[120px] rounded-2xl border border-slate-200 bg-white p-3 shadow-sm" wire:key="day-{{ $dateKey }}">
                <div class="mb-2 flex items-center justify-between">
                    <span @class([
                        'text-sm font-bold',
                        'text-teal-700' => $date->isToday(),
                        'text-slate-900' => ! $date->isToday(),
                    ])>{{ $day }}</span>
                    @if ($dayAppointments->isNotEmpty())
                        <span class="rounded-full bg-teal-100 px-2 py-0.5 text-[10px] font-bold text-teal-800">{{ $dayAppointments->count() }}</span>
                    @endif
                </div>
                <div class="space-y-2">
                    @foreach ($dayAppointments as $appointment)
                        <button
                            class="w-full rounded-lg border border-slate-100 bg-slate-50 px-2 py-1.5 text-left hover:bg-teal-50"
                            type="button"
                            wire:click="openForm({{ $appointment->id }})"
                        >
                            <p class="truncate text-xs font-semibold text-slate-900">{{ $appointment->starts_at->format('g:i A') }} · {{ $appointment->title }}</p>
                            <p class="truncate text-[10px] text-slate-500">{{ $appointment->lead?->fullName() ?? 'No contact' }}</p>
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">
                <h3 class="text-lg font-bold text-slate-900">{{ $editingAppointmentId ? 'Edit Appointment' : 'Schedule Appointment' }}</h3>
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
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Meeting Type</label>
                            <select class="w-full rounded-xl border-slate-200 shadow-sm" wire:model="meeting_type">
                                @foreach ($meetingTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
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
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Starts At *</label>
                            <input class="w-full rounded-xl border-slate-200 shadow-sm" type="datetime-local" wire:model="starts_at" />
                            @error('starts_at') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Ends At</label>
                            <input class="w-full rounded-xl border-slate-200 shadow-sm" type="datetime-local" wire:model="ends_at" />
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Location</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="location" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Video / meeting link</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="url" wire:model="zoom_link" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Reminder Notes</label>
                        <textarea class="w-full rounded-xl border-slate-200 shadow-sm" rows="2" wire:model="reminder_notes"></textarea>
                    </div>
                    <div class="flex flex-wrap justify-end gap-2">
                        @if ($editingAppointmentId)
                            <button class="rounded-full border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700" type="button" wire:click="cancelAppointment({{ $editingAppointmentId }})">Cancel Appointment</button>
                            <button class="rounded-full border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700" type="button" wire:click="deleteAppointment({{ $editingAppointmentId }})" wire:confirm="Delete this appointment?">Delete</button>
                        @endif
                        <button class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700" type="button" wire:click="closeForm">Close</button>
                        <button class="rounded-full bg-teal-600 px-4 py-2 text-sm font-semibold text-white" type="submit">Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
