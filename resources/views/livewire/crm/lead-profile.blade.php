<div class="p-4 sm:p-6 lg:p-8">
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <a class="text-sm font-semibold text-teal-700 hover:text-teal-900" href="{{ $this->indexUrl() }}">
                ← Back to {{ $lead->lifecycle->label() }} list
            </a>
            <p class="mt-2 text-xs font-bold uppercase tracking-[0.2em] text-teal-600">{{ $lead->lifecycle->label() }}</p>
            <h1 class="mt-1 text-3xl font-bold text-slate-900">{{ $lead->fullName() }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $lead->email ?? $lead->phone ?? 'No contact details' }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('update', $lead)
                <a
                    class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                    href="{{ $this->editUrl() }}"
                >
                    Edit
                </a>
            @endcan
            @if ($this->canConvertToProspect())
                <button
                    class="inline-flex items-center justify-center rounded-full bg-cyan-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-cyan-700"
                    type="button"
                    wire:click="convertTo('prospect')"
                    wire:confirm="Convert this lead to a prospect?"
                >
                    Convert to Prospect
                </button>
            @endif
            @if ($this->canConvertToClient())
                <button
                    class="inline-flex items-center justify-center rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                    type="button"
                    wire:click="convertTo('client')"
                    wire:confirm="Convert this prospect to a customer?"
                >
                    Convert to Customer
                </button>
            @endif
            @if ($this->canConvertToRecruit())
                <button
                    class="inline-flex items-center justify-center rounded-full bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-violet-700"
                    type="button"
                    wire:click="convertTo('recruit')"
                    wire:confirm="{{ $lead->lifecycle === \App\Enums\Crm\LeadLifecycle::Client ? 'Mark this customer as a recruit too (type B)?' : 'Convert this record to a recruit (type R)?' }}"
                >
                    {{ $lead->lifecycle === \App\Enums\Crm\LeadLifecycle::Client ? 'Mark as Recruit (B)' : 'Convert to Recruit' }}
                </button>
            @endif
            @can('delete', $lead)
                <button
                    class="inline-flex items-center justify-center rounded-full border border-rose-200 bg-rose-50 px-5 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100"
                    type="button"
                    wire:click="deleteRecord"
                    wire:confirm="Delete this record permanently?"
                >
                    Delete
                </button>
            @endcan
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-1">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Details</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="font-semibold text-slate-500">Lead Status</dt>
                        <dd class="text-slate-900">{{ $lead->status?->label() ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Temperature</dt>
                        <dd class="capitalize text-slate-900">{{ $lead->temperature?->label() }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Score</dt>
                        <dd class="text-slate-900">{{ $lead->score }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Source</dt>
                        <dd class="text-slate-900">{{ $lead->source?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Stage</dt>
                        <dd class="text-slate-900">{{ $lead->stage?->name ?? '—' }}</dd>
                    </div>
                    @if ($lead->lostReasonLabel())
                        <div>
                            <dt class="font-semibold text-slate-500">Lost Reason</dt>
                            <dd class="text-slate-900">{{ $lead->lostReasonLabel() }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="font-semibold text-slate-500">Assigned To</dt>
                        <dd class="text-slate-900">{{ $lead->assignedUser?->name ?? 'Unassigned' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Next Follow-Up</dt>
                        <dd class="text-slate-900">{{ $lead->next_follow_up_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                    </div>
                    @if ($lead->address || $lead->city || $lead->state || $lead->country)
                        <div>
                            <dt class="font-semibold text-slate-500">Address</dt>
                            <dd class="text-slate-900">
                                @if ($lead->address)
                                    <span class="block">{{ $lead->address }}</span>
                                @endif
                                {{ collect([$lead->city, $lead->state, $lead->country])->filter()->implode(', ') }}
                            </dd>
                        </div>
                    @endif
                    @if ($lead->best_time_to_contact)
                        <div>
                            <dt class="font-semibold text-slate-500">Best Time to Contact</dt>
                            <dd class="text-slate-900">{{ config('crm.prospect_best_times_to_contact.'.$lead->best_time_to_contact, $lead->best_time_to_contact) }}</dd>
                        </div>
                    @endif
                    @if ($lead->occupation)
                        <div>
                            <dt class="font-semibold text-slate-500">Occupation</dt>
                            <dd class="text-slate-900">{{ $lead->occupation }}</dd>
                        </div>
                    @endif
                    @if ($lead->spouse_name)
                        <div>
                            <dt class="font-semibold text-slate-500">Spouse Name</dt>
                            <dd class="text-slate-900">{{ $lead->spouse_name }}</dd>
                        </div>
                    @endif
                    @if ($lead->spouse_occupation)
                        <div>
                            <dt class="font-semibold text-slate-500">Spouse Occupation</dt>
                            <dd class="text-slate-900">{{ $lead->spouse_occupation }}</dd>
                        </div>
                    @endif
                    @if ($lead->interested_in)
                        <div>
                            <dt class="font-semibold text-slate-500">Interested In</dt>
                            <dd class="text-slate-900">{{ $lead->interested_in }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            @if ($lead->tags->isNotEmpty())
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-900">Tags</h2>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($lead->tags as $tag)
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                {{ $tag->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($lead->message)
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-900">{{ $lead->lifecycle === \App\Enums\Crm\LeadLifecycle::Prospect ? 'Notes' : 'Message' }}</h2>
                    <p class="mt-3 whitespace-pre-wrap text-sm text-slate-600">{{ $lead->message }}</p>
                </div>
            @endif

            <livewire:crm.lead-engagement-panel :lead="$lead" :key="'engagement-'.$lead->id" />
        </div>

        <div class="space-y-6 lg:col-span-2">
            @if ($lead->referredBy)
                <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-blue-700">Referred By</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $lead->referredBy->fullName() }}</p>
                </div>
            @endif

            <livewire:crm.lead-after-sales-panel :lead="$lead" :key="'after-sales-'.$lead->id" />
            <livewire:crm.lead-referrals-panel :lead="$lead" :key="'referrals-'.$lead->id" />

            @can('update', $lead)
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-900">Add Note</h2>
                    <form class="mt-4 space-y-3" wire:submit="addNote">
                        <textarea
                            class="w-full rounded-xl border-slate-200 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                            placeholder="Log a conversation, follow-up, or internal note..."
                            rows="4"
                            wire:model="noteBody"
                        ></textarea>
                        @error('noteBody') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        <button
                            class="rounded-full bg-gradient-to-r from-teal-600 to-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm"
                            type="submit"
                        >
                            Save Note
                        </button>
                    </form>
                </div>
            @endcan

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Timeline</h2>
                <div class="mt-4">
                    <livewire:crm.lead-timeline :lead="$lead" :key="'timeline-'.$lead->id" />
                </div>
            </div>
        </div>
    </div>
</div>
