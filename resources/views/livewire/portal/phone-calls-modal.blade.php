<div>
    @if ($show)
        <div class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0" role="dialog" aria-modal="true" aria-labelledby="phone-calls-title">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative mx-auto w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-700">Quick Action</p>
                        <h2 id="phone-calls-title" class="mt-1 text-xl font-black text-slate-950">Phone Calls</h2>
                        <p class="mt-1 text-sm text-slate-500">Synced with your CRM calendar · sorted by soonest call first.</p>
                    </div>
                    <button type="button" wire:click="close" class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                @if (session('phone_call_status'))
                    <div class="mx-5 mt-4 rounded-lg border border-teal-100 bg-teal-50 px-4 py-3 text-sm font-medium text-teal-800 sm:mx-6">
                        {{ session('phone_call_status') }}
                    </div>
                @endif

                <div class="max-h-[70vh] overflow-y-auto px-5 py-5 sm:px-6">
                    <section>
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h3 class="text-sm font-bold text-slate-900">Call list</h3>
                            <a href="{{ route(\App\Support\Crm\CrmRoutes::name('calendar.index')) }}" class="text-xs font-semibold text-teal-700 hover:text-teal-900">
                                Open Calendar
                            </a>
                        </div>

                        <ul class="space-y-2">
                            @forelse ($upcomingCalls as $call)
                                @php
                                    $isDone = $call->status?->value === 'completed';
                                    $isPendingResult = $resultsEventId === $call->id;
                                    $contactName = $call->lead()?->fullName()
                                        ?? ($call->metadata['other_contact_name'] ?? null)
                                        ?? $call->attendees->first()?->user?->name;
                                    $displayPhone = app(\App\Services\Portal\PhoneCallService::class)->displayPhone($call);
                                    $reasonLabel = \App\Support\Portal\PhoneCallReasons::label($call->metadata['phone_call_reason'] ?? '');
                                    $resultLabel = \App\Support\Portal\PhoneCallResults::label($call->metadata['phone_call_result'] ?? '');
                                @endphp
                                <li @class([
                                    'flex items-start gap-2 rounded-xl border px-3 py-2.5 sm:gap-3',
                                    'border-emerald-100 bg-emerald-50/70' => $isDone,
                                    'border-slate-100 bg-slate-50' => ! $isDone,
                                ])>
                                    <input
                                        type="checkbox"
                                        class="mt-0.5 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                                        @checked($isDone || $isPendingResult)
                                        @disabled($isDone)
                                        wire:click.prevent="beginCompleteCall({{ $call->id }})"
                                    >
                                    <div class="min-w-0 flex-1 space-y-0.5">
                                        <p @class([
                                            'truncate text-sm font-semibold text-slate-900',
                                            'line-through opacity-70' => $isDone || $isPendingResult,
                                        ])>
                                            {{ $contactName ?? $call->title }}
                                            @if ($displayPhone)
                                                <span class="font-medium text-teal-700">· {{ $displayPhone }}</span>
                                            @endif
                                        </p>
                                        <p class="truncate text-xs text-slate-500">
                                            {{ $call->start_at?->format('M j · g:i A') }}
                                            @if ($isDone && $resultLabel)
                                                · {{ $resultLabel }}
                                            @elseif ($reasonLabel)
                                                · {{ $reasonLabel }}
                                            @endif
                                        </p>
                                    </div>
                                    @if (! $isDone)
                                        <button
                                            type="button"
                                            wire:click="openEditCall({{ $call->id }})"
                                            class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-white hover:text-teal-700"
                                            title="Edit or reschedule"
                                            aria-label="Edit or reschedule call"
                                        >
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 7.125 16.862 4.487" /></svg>
                                        </button>
                                    @endif
                                </li>
                            @empty
                                <li class="rounded-xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">
                                    No phone calls scheduled yet.
                                </li>
                            @endforelse
                        </ul>
                    </section>

                    <div class="my-6 border-t border-slate-200"></div>

                    <section>
                        <h3 class="text-sm font-bold text-slate-900">Schedule a phone call</h3>
                        <p class="mt-1 text-xs text-slate-500">Search for a prospect, customer, or team member — or type a new name.</p>

                        <form class="mt-4 space-y-4" wire:submit="schedule">
                            <div class="relative">
                                <label for="phone-call-contact-search" class="mb-1 block text-sm font-semibold text-slate-700">
                                    Search contact
                                </label>
                                <input
                                    id="phone-call-contact-search"
                                    type="search"
                                    wire:model.live.debounce.300ms="contact_search"
                                    placeholder="Type at least 3 characters…"
                                    class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                    @disabled($show_new_prospect_form || $contact_type === 'other')
                                    autocomplete="off"
                                />
                                @if ($contact_id || ($contact_type === 'other' && filled($other_name)))
                                    <div class="mt-2 flex items-center justify-between gap-2 rounded-lg border border-teal-200 bg-teal-50 px-3 py-2">
                                        <p class="text-sm font-semibold text-teal-900">
                                            {{ $contact_type === 'other' ? $other_name : $contact_search }}
                                            @if ($contact_type === 'team')
                                                <span class="text-xs font-bold uppercase tracking-wide text-sky-700">· Team</span>
                                            @elseif ($contact_type === 'customer')
                                                <span class="text-xs font-bold uppercase tracking-wide text-emerald-700">· Customer</span>
                                            @elseif ($contact_type === 'other')
                                                <span class="text-xs font-bold uppercase tracking-wide text-amber-700">· Other</span>
                                            @endif
                                        </p>
                                        <button type="button" wire:click="clearContact" class="text-xs font-semibold text-teal-700 hover:text-teal-900">
                                            Change
                                        </button>
                                    </div>
                                @endif
                                @error('contact_search') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                                @if ($showContactResults)
                                    <ul class="absolute z-10 mt-1 max-h-48 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                                        @forelse ($contactResults as $contact)
                                            <li>
                                                <button
                                                    type="button"
                                                    wire:click="selectContact('{{ $contact['kind'] }}', {{ $contact['id'] }})"
                                                    class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-teal-50"
                                                >
                                                    <span class="font-semibold text-slate-900">{{ $contact['label'] }}</span>
                                                    <span @class([
                                                        'shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide',
                                                        'bg-teal-100 text-teal-800' => $contact['kind'] === 'prospect',
                                                        'bg-emerald-100 text-emerald-800' => $contact['kind'] === 'customer',
                                                        'bg-sky-100 text-sky-800' => $contact['kind'] === 'team',
                                                    ])>
                                                        @if ($contact['kind'] === 'customer')
                                                            Customer
                                                        @elseif ($contact['kind'] === 'team')
                                                            Team
                                                        @else
                                                            Prospect
                                                        @endif
                                                    </span>
                                                </button>
                                            </li>
                                        @empty
                                            <li class="px-3 py-2 text-xs text-slate-500">No matching contacts. Keep typing to use a new name.</li>
                                        @endforelse
                                    </ul>
                                @endif
                            </div>

                            @if ($show_add_prospect_prompt)
                                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                                    <p class="text-sm font-semibold text-amber-950">
                                        “{{ trim($contact_search) }}” is not in your contact list yet.
                                    </p>
                                    <p class="mt-1 text-sm text-amber-900">Add them as a prospect, or schedule as another contact.</p>
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            wire:click="confirmAddProspect"
                                            class="rounded-full bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700"
                                        >
                                            Yes, add prospect
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="useOtherContact"
                                            class="rounded-full border border-amber-300 bg-white px-4 py-2 text-sm font-semibold text-amber-900 hover:bg-amber-100"
                                        >
                                            Use as other contact
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="cancelAddProspect"
                                            class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                        >
                                            Go back
                                        </button>
                                    </div>
                                </div>
                            @endif

                            @if ($show_new_prospect_form)
                                <div class="rounded-xl border border-teal-200 bg-teal-50/60 p-4">
                                    <h4 class="text-sm font-bold text-slate-900">New prospect details</h4>
                                    <p class="mt-1 text-xs text-slate-600">Required fields to add this contact before scheduling the call.</p>
                                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label for="phone-new-first-name" class="mb-1 block text-sm font-semibold text-slate-700">First name</label>
                                            <input id="phone-new-first-name" type="text" wire:model="new_first_name" class="block w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" required />
                                            @error('new_first_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="phone-new-last-name" class="mb-1 block text-sm font-semibold text-slate-700">Last name</label>
                                            <input id="phone-new-last-name" type="text" wire:model="new_last_name" class="block w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                                            @error('new_last_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="phone-new-email" class="mb-1 block text-sm font-semibold text-slate-700">Email</label>
                                            <input id="phone-new-email" type="email" wire:model="new_email" class="block w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                                            @error('new_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="phone-new-phone" class="mb-1 block text-sm font-semibold text-slate-700">Phone</label>
                                            <input id="phone-new-phone" type="tel" wire:model="new_phone" class="block w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                                            @error('new_phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if ($showPhoneField)
                                <div>
                                    <label for="phone-call-number" class="mb-1 block text-sm font-semibold text-slate-700">Phone number</label>
                                    <input
                                        id="phone-call-number"
                                        type="tel"
                                        wire:model="phone_number"
                                        placeholder="(555) 123-4567"
                                        class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                        required
                                    />
                                    @error('phone_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            <div>
                                <label for="phone-call-when" class="mb-1 block text-sm font-semibold text-slate-700">When</label>
                                <select id="phone-call-when" wire:model.live="call_when" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                    <option value="in_15">In 15 minutes</option>
                                    <option value="in_30">In 30 minutes</option>
                                    <option value="in_60">In 1 hour</option>
                                    <option value="today_14">Today at 2:00 PM</option>
                                    <option value="today_16">Today at 4:00 PM</option>
                                    <option value="tomorrow_10">Tomorrow at 10:00 AM</option>
                                    <option value="custom">Pick date & time</option>
                                </select>
                                @error('call_when') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            @if ($call_when === 'custom')
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="phone-call-date" class="mb-1 block text-sm font-semibold text-slate-700">Date</label>
                                        <input
                                            id="phone-call-date"
                                            type="date"
                                            wire:model="call_date"
                                            class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                            required
                                        />
                                        @error('call_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="phone-call-time" class="mb-1 block text-sm font-semibold text-slate-700">Time</label>
                                        <input
                                            id="phone-call-time"
                                            type="time"
                                            wire:model="call_time"
                                            class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                            required
                                        />
                                        @error('call_time') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            @endif

                            <div>
                                <label for="phone-call-reason" class="mb-1 block text-sm font-semibold text-slate-700">Reason for call</label>
                                <select
                                    id="phone-call-reason"
                                    wire:model.live="call_reason"
                                    class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                    required
                                >
                                    <option value="">Select a reason…</option>
                                    @foreach ($reasonOptions as $reason)
                                        <option value="{{ $reason['value'] }}">{{ $reason['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('call_reason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="phone-call-notes" class="mb-1 block text-sm font-semibold text-slate-700">
                                    Notes
                                    @if ($call_reason === 'other')
                                        <span class="font-normal text-amber-700">(required for Other)</span>
                                    @else
                                        <span class="font-normal text-slate-400">(optional)</span>
                                    @endif
                                </label>
                                <textarea
                                    id="phone-call-notes"
                                    wire:model="notes"
                                    rows="3"
                                    placeholder="{{ $call_reason === 'other' ? 'Describe the purpose of this call…' : 'Add any extra context…' }}"
                                    class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                ></textarea>
                                @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                                <button type="button" wire:click="close" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">
                                    Cancel
                                </button>
                                @if ($show_new_prospect_form)
                                    <x-primary-button type="button" wire:click="createProspectAndSchedule">
                                        Add prospect & schedule
                                    </x-primary-button>
                                @else
                                    <x-primary-button type="submit">
                                        Schedule call
                                    </x-primary-button>
                                @endif
                            </div>
                        </form>
                    </section>
                </div>
            </div>

            @if ($showResults)
                <div class="fixed inset-0 z-[60] flex items-center justify-center px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="phone-call-results-title">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="cancelCallResults"></div>
                    <div class="relative w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                        <div class="border-b border-slate-100 px-5 py-4">
                            <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-700">Call Results</p>
                            <h3 id="phone-call-results-title" class="mt-1 text-lg font-black text-slate-950">{{ $resultsContactLabel }}</h3>
                            <p class="mt-1 text-xs text-slate-500">Saved to your calendar and CRM activity log.</p>
                        </div>
                        <form class="space-y-4 px-5 py-5" wire:submit="saveCallResults">
                            <div>
                                <label for="phone-call-result" class="mb-1 block text-sm font-semibold text-slate-700">Call result</label>
                                <select
                                    id="phone-call-result"
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
                                <label for="phone-call-result-comments" class="mb-1 block text-sm font-semibold text-slate-700">
                                    Comments <span class="font-normal text-slate-400">(optional)</span>
                                </label>
                                <textarea
                                    id="phone-call-result-comments"
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
                                            <label for="phone-call-reschedule-when" class="mb-1 block text-sm font-semibold text-slate-700">When</label>
                                            <select id="phone-call-reschedule-when" wire:model.live="reschedule_when" class="block w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
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
                                                    <label for="phone-call-reschedule-date" class="mb-1 block text-sm font-semibold text-slate-700">Date</label>
                                                    <input
                                                        id="phone-call-reschedule-date"
                                                        type="date"
                                                        wire:model="reschedule_date"
                                                        class="block w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                                        required
                                                    />
                                                    @error('reschedule_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                                </div>
                                                <div>
                                                    <label for="phone-call-reschedule-time" class="mb-1 block text-sm font-semibold text-slate-700">Time</label>
                                                    <input
                                                        id="phone-call-reschedule-time"
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
                                            <label for="phone-call-reschedule-reason" class="mb-1 block text-sm font-semibold text-slate-700">Reason for follow-up</label>
                                            <select id="phone-call-reschedule-reason" wire:model.live="reschedule_reason" class="block w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                                <option value="">Select a reason…</option>
                                                @foreach ($rescheduleReasonOptions as $reason)
                                                    <option value="{{ $reason['value'] }}">{{ $reason['label'] }}</option>
                                                @endforeach
                                            </select>
                                            @error('reschedule_reason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="phone-call-reschedule-notes" class="mb-1 block text-sm font-semibold text-slate-700">
                                                Follow-up notes
                                                @if ($reschedule_reason === 'other')
                                                    <span class="font-normal text-amber-700">(required for Other)</span>
                                                @else
                                                    <span class="font-normal text-slate-400">(optional)</span>
                                                @endif
                                            </label>
                                            <textarea
                                                id="phone-call-reschedule-notes"
                                                wire:model="reschedule_notes"
                                                rows="2"
                                                placeholder="Context for the next call…"
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

            @if ($showEdit)
                <div class="fixed inset-0 z-[60] flex items-center justify-center px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="phone-call-edit-title">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="cancelEditCall"></div>
                    <div class="relative w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                        <div class="border-b border-slate-100 px-5 py-4">
                            <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-700">Edit Call</p>
                            <h3 id="phone-call-edit-title" class="mt-1 text-lg font-black text-slate-950">Reschedule or update</h3>
                        </div>
                        <form class="max-h-[70vh] space-y-4 overflow-y-auto px-5 py-5" wire:submit="saveEditCall">
                            <div>
                                <label for="phone-call-edit-when" class="mb-1 block text-sm font-semibold text-slate-700">When</label>
                                <select id="phone-call-edit-when" wire:model.live="edit_call_when" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                    <option value="in_15">In 15 minutes</option>
                                    <option value="in_30">In 30 minutes</option>
                                    <option value="in_60">In 1 hour</option>
                                    <option value="today_14">Today at 2:00 PM</option>
                                    <option value="today_16">Today at 4:00 PM</option>
                                    <option value="tomorrow_10">Tomorrow at 10:00 AM</option>
                                    <option value="custom">Pick date & time</option>
                                </select>
                                @error('edit_call_when') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            @if ($edit_call_when === 'custom')
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="phone-call-edit-date" class="mb-1 block text-sm font-semibold text-slate-700">Date</label>
                                        <input
                                            id="phone-call-edit-date"
                                            type="date"
                                            wire:model="edit_call_date"
                                            class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                            required
                                        />
                                        @error('edit_call_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="phone-call-edit-time" class="mb-1 block text-sm font-semibold text-slate-700">Time</label>
                                        <input
                                            id="phone-call-edit-time"
                                            type="time"
                                            wire:model="edit_call_time"
                                            class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                            required
                                        />
                                        @error('edit_call_time') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            @endif
                            <div>
                                <label for="phone-call-edit-number" class="mb-1 block text-sm font-semibold text-slate-700">Phone number</label>
                                <input
                                    id="phone-call-edit-number"
                                    type="tel"
                                    wire:model="edit_phone_number"
                                    class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                    required
                                />
                                @error('edit_phone_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="phone-call-edit-reason" class="mb-1 block text-sm font-semibold text-slate-700">Reason for call</label>
                                <select id="phone-call-edit-reason" wire:model.live="edit_call_reason" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" required>
                                    <option value="">Select a reason…</option>
                                    @foreach ($editReasonOptions as $reason)
                                        <option value="{{ $reason['value'] }}">{{ $reason['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('edit_call_reason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="phone-call-edit-notes" class="mb-1 block text-sm font-semibold text-slate-700">
                                    Notes
                                    @if ($edit_call_reason === 'other')
                                        <span class="font-normal text-amber-700">(required for Other)</span>
                                    @else
                                        <span class="font-normal text-slate-400">(optional)</span>
                                    @endif
                                </label>
                                <textarea
                                    id="phone-call-edit-notes"
                                    wire:model="edit_notes"
                                    rows="3"
                                    class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                ></textarea>
                                @error('edit_notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                                <button type="button" wire:click="cancelEditCall" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">
                                    Cancel
                                </button>
                                <x-primary-button type="submit">
                                    Save changes
                                </x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
