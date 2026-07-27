@php
    $display = fn (?string $value, string $fallback = '—') => filled($value) ? $value : $fallback;
    $bestTime = $bestTimesToContact[$lead->best_time_to_contact] ?? $lead->best_time_to_contact;
@endphp

<div class="p-4 sm:p-6 lg:p-8">
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="mx-auto max-w-4xl">
        <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-teal-600">Prospect</p>
                <h1 class="text-2xl font-bold text-slate-900">{{ $lead->fullName() }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $lead->email ?? $lead->phone ?? 'No contact details' }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a
                    class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm hover:bg-slate-50"
                    href="{{ $this->indexUrl() }}"
                >
                    ← Back to prospect list
                </a>
                @can('update', $lead)
                    <a
                        class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                        href="{{ $this->editUrl() }}"
                    >
                        Edit
                    </a>
                @endcan
                @if ($this->canConvertToClient())
                    <button
                        class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                        type="button"
                        wire:click="convertTo('client')"
                        wire:confirm="Convert this prospect to a customer?"
                    >
                        Convert to Customer
                    </button>
                @endif
                @if ($this->canConvertToRecruit())
                    <button
                        class="rounded-full bg-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-violet-700"
                        type="button"
                        wire:click="convertTo('recruit')"
                        wire:confirm="Convert this prospect to a recruit (type R)?"
                    >
                        Convert to Recruit
                    </button>
                @endif
                @can('delete', $lead)
                    <button
                        class="rounded-full border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100"
                        type="button"
                        wire:click="deleteRecord"
                        wire:confirm="Delete this record permanently?"
                    >
                        Delete
                    </button>
                @endcan
            </div>
        </div>

        <div class="space-y-4">
            {{-- Contact Information --}}
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-gradient-to-r from-teal-50 to-slate-100 px-4 py-2.5">
                    <h2 class="text-sm font-bold text-slate-900">Contact Information</h2>
                </div>
                <div class="bg-slate-100 p-4">
                    <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">First Name</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $display($lead->first_name) }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Last Name</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $display($lead->last_name) }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Email</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $display($lead->email) }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Phone</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $display($lead->phone) }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm sm:col-span-2 lg:col-span-4">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Address</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $display($lead->address) }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">City</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $display($lead->city) }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">State</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $display($lead->state) }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Company</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $display($lead->company) }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Occupation</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $display($lead->occupation) }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Spouse Name</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $display($lead->spouse_name) }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Spouse Occupation</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $display($lead->spouse_occupation) }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Best Time to Contact</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $display($bestTime) }}</dd>
                        </div>
                        @if ($lead->message)
                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm sm:col-span-2">
                                <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Notes</dt>
                                <dd class="mt-0.5 whitespace-pre-wrap text-sm text-slate-900">{{ $lead->message }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </section>

            {{-- CRM Details --}}
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-gradient-to-r from-slate-100 to-slate-200/60 px-4 py-2.5">
                    <h2 class="text-sm font-bold text-slate-900">CRM Details</h2>
                    <p class="text-[11px] text-slate-500">Pipeline and engagement settings</p>
                </div>
                <div class="bg-slate-100 p-4">
                    <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Business Line</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $lead->business_line?->label() ?? '—' }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Lead Status</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $lead->status?->label() ?? '—' }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Temperature</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $lead->temperature?->label() ?? '—' }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Score</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $lead->score }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Source</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $lead->source?->name ?? '—' }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Funnel Stage</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $lead->stage?->name ?? '—' }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Assigned To</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $lead->assignedUser?->name ?? 'Unassigned' }}</dd>
                        </div>
                        @if ($lead->referredBy)
                            <div class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 shadow-sm sm:col-span-2">
                                <dt class="text-[11px] font-semibold uppercase tracking-wide text-blue-700">Referred By</dt>
                                <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $lead->referredBy->fullName() }}</dd>
                            </div>
                        @endif
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Next Follow-Up</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $lead->next_follow_up_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Last Contacted</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $lead->last_contacted_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                        </div>
                        @if ($lead->lostReasonLabel())
                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm sm:col-span-2">
                                <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Lost Reason</dt>
                                <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $lead->lostReasonLabel() }}</dd>
                            </div>
                        @endif
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Consent</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $lead->consent_given ? 'Given' : 'Not given' }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            {{-- Sales workflow --}}
            <livewire:crm.lead-demonstrations-panel :lead="$lead" :key="'demos-'.$lead->id" />
            <livewire:crm.lead-consultations-panel :lead="$lead" :key="'consultations-'.$lead->id" />
            <livewire:crm.lead-quotations-panel :lead="$lead" :key="'quotations-'.$lead->id" />
            <livewire:crm.lead-orders-panel :lead="$lead" :key="'orders-'.$lead->id" />

            {{-- Activities --}}
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <livewire:crm.lead-activities-panel :lead="$lead" :key="'activities-'.$lead->id" />
            </section>

            @if ($lead->tags->isNotEmpty())
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 bg-gradient-to-r from-slate-100 to-slate-200/60 px-4 py-2.5">
                        <h2 class="text-sm font-bold text-slate-900">Profile Tags</h2>
                    </div>
                    <div class="flex flex-wrap gap-2 bg-slate-100 p-4">
                        @foreach ($lead->tags as $tag)
                            <span class="rounded-full border border-teal-200 bg-white px-2.5 py-1 text-xs font-semibold text-teal-800 shadow-sm">
                                {{ $tag->name }}
                            </span>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-gradient-to-r from-slate-100 to-slate-200/60 px-4 py-2.5">
                    <h2 class="text-sm font-bold text-slate-900">Timeline</h2>
                    <p class="text-[11px] text-slate-500">System events, notes, and activity history.</p>
                </div>
                <div class="bg-slate-100 p-4">
                    @can('update', $lead)
                        <div class="mb-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                            <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Add Note</h3>
                            <form class="mt-3 space-y-3" wire:submit="addNote">
                                <textarea
                                    class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                    placeholder="Log a conversation or internal note..."
                                    rows="3"
                                    wire:model="noteBody"
                                ></textarea>
                                @error('noteBody') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                                <button
                                    class="rounded-full bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700"
                                    type="submit"
                                >
                                    Save Note
                                </button>
                            </form>
                        </div>
                    @endcan
                    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <livewire:crm.lead-timeline :lead="$lead" :key="'timeline-'.$lead->id" />
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
