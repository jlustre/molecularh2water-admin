<div>
    @if ($show)
        <div class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0" role="dialog" aria-modal="true" aria-labelledby="meetings-title">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative mx-auto w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-indigo-700">Quick Action</p>
                        <h2 id="meetings-title" class="mt-1 text-xl font-black text-slate-950">Meetings</h2>
                        <p class="mt-1 text-sm text-slate-500">Synced with your CRM calendar · supports recurring meetings.</p>
                    </div>
                    <button type="button" wire:click="close" class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                @if (session('meeting_status'))
                    <div class="mx-5 mt-4 rounded-lg border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm font-medium text-indigo-800 sm:mx-6">
                        {{ session('meeting_status') }}
                    </div>
                @endif

                <div class="max-h-[70vh] overflow-y-auto px-5 py-5 sm:px-6">
                    <section>
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h3 class="text-sm font-bold text-slate-900">Upcoming meetings</h3>
                            <a href="{{ route(\App\Support\Crm\CrmRoutes::name('calendar.index')) }}" class="text-xs font-semibold text-indigo-700 hover:text-indigo-900">
                                Open Calendar
                            </a>
                        </div>

                        <ul class="space-y-2">
                            @forelse ($upcomingMeetings as $meeting)
                                @php
                                    $isDone = $meeting->status?->value === 'completed';
                                    $contactName = app(\App\Services\Portal\MeetingService::class)->contactLabel($meeting);
                                    $isRecurring = ($meeting->metadata['recurrence_total'] ?? 1) > 1;
                                @endphp
                                <li @class([
                                    'rounded-xl border px-3 py-2.5',
                                    'border-emerald-100 bg-emerald-50/70' => $isDone,
                                    'border-slate-100 bg-slate-50' => ! $isDone,
                                ])>
                                    <p @class([
                                        'truncate text-sm font-semibold text-slate-900',
                                        'line-through opacity-70' => $isDone,
                                    ])>
                                        {{ $contactName }}
                                        <span class="font-medium text-indigo-700">· {{ $meeting->type?->name }}</span>
                                    </p>
                                    <p class="truncate text-xs text-slate-500">
                                        {{ $meeting->start_at?->format('M j · g:i A') }}
                                        @if ($isRecurring)
                                            · Recurring
                                        @endif
                                        @if ($meeting->location)
                                            · {{ $meeting->location }}
                                        @elseif ($meeting->meeting_link)
                                            · Online
                                        @endif
                                    </p>
                                </li>
                            @empty
                                <li class="rounded-xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">
                                    No meetings scheduled yet.
                                </li>
                            @endforelse
                        </ul>
                    </section>

                    <div class="my-6 border-t border-slate-200"></div>

                    <section>
                        <h3 class="text-sm font-bold text-slate-900">Schedule a meeting</h3>
                        <p class="mt-1 text-xs text-slate-500">Search for a contact, choose format and time, and optionally set a repeat schedule.</p>

                        <form class="mt-4 space-y-4" wire:submit="schedule">
                            <div class="grid gap-4 sm:grid-cols-5">
                                <div class="relative sm:col-span-3">
                                    <label for="meeting-contact-search" class="mb-1 block text-sm font-semibold text-slate-700">
                                        Search contact
                                    </label>
                                    <input
                                        id="meeting-contact-search"
                                        type="search"
                                        wire:model.live.debounce.300ms="contact_search"
                                        placeholder="Type at least 3 characters…"
                                        class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        @disabled($show_new_prospect_form || $contact_type === 'other')
                                        autocomplete="off"
                                    />
                                    @if ($contact_id || ($contact_type === 'other' && filled($other_name)))
                                        <div class="mt-2 flex items-center justify-between gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2">
                                            <p class="truncate text-sm font-semibold text-indigo-900">
                                                {{ $contact_type === 'other' ? $other_name : $contact_search }}
                                            </p>
                                            <button type="button" wire:click="clearContact" class="shrink-0 text-xs font-semibold text-indigo-700 hover:text-indigo-900">
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
                                                        class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-indigo-50"
                                                    >
                                                        <span class="font-semibold text-slate-900">{{ $contact['label'] }}</span>
                                                        <span @class([
                                                            'shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide',
                                                            'bg-indigo-100 text-indigo-800' => $contact['kind'] === 'prospect',
                                                            'bg-emerald-100 text-emerald-800' => $contact['kind'] === 'customer',
                                                            'bg-slate-100 text-slate-700' => $contact['kind'] === 'team',
                                                        ])>
                                                            {{ match ($contact['kind']) {
                                                                'customer' => 'Customer',
                                                                'team' => 'Team',
                                                                default => 'Prospect',
                                                            } }}
                                                        </span>
                                                    </button>
                                                </li>
                                            @empty
                                                <li class="px-3 py-2 text-xs text-slate-500">No matching contacts.</li>
                                            @endforelse
                                        </ul>
                                    @endif
                                </div>

                                <div class="sm:col-span-2">
                                    <label for="meeting-invitee-group" class="mb-1 block text-sm font-semibold text-slate-700">
                                        Other invitees
                                    </label>
                                    <select
                                        id="meeting-invitee-group"
                                        wire:model.live="invitee_group"
                                        class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        @disabled($show_new_prospect_form)
                                    >
                                        @foreach ($inviteeGroups as $group)
                                            <option value="{{ $group['value'] }}">{{ $group['label'] }}</option>
                                        @endforeach
                                    </select>
                                    @if ($invitee_group !== '' && $inviteeCount > 0)
                                        <p class="mt-1 text-xs text-slate-500">{{ $inviteeCount }} {{ str('person')->plural($inviteeCount) }} will be invited.</p>
                                    @elseif ($invitee_group !== '')
                                        <p class="mt-1 text-xs text-amber-600">No matching users found for this group.</p>
                                    @endif
                                </div>
                            </div>

                            @if ($show_add_prospect_prompt)
                                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                                    <p class="text-sm font-semibold text-amber-950">
                                        “{{ trim($contact_search) }}” is not in your contact list yet.
                                    </p>
                                    <p class="mt-1 text-sm text-amber-900">Add as a prospect, use as another contact, or go back.</p>
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <button type="button" wire:click="confirmAddProspect" class="rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                            Yes, add prospect
                                        </button>
                                        <button type="button" wire:click="useOtherContact" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                            Use as other contact
                                        </button>
                                        <button type="button" wire:click="cancelAddProspect" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                            Go back
                                        </button>
                                    </div>
                                </div>
                            @endif

                            @if ($show_new_prospect_form)
                                <div class="rounded-xl border border-indigo-200 bg-indigo-50/60 p-4">
                                    <h4 class="text-sm font-bold text-slate-900">New prospect details</h4>
                                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label for="meeting-new-first-name" class="mb-1 block text-sm font-semibold text-slate-700">First name</label>
                                            <input id="meeting-new-first-name" type="text" wire:model="new_first_name" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                            @error('new_first_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="meeting-new-last-name" class="mb-1 block text-sm font-semibold text-slate-700">Last name</label>
                                            <input id="meeting-new-last-name" type="text" wire:model="new_last_name" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                        </div>
                                        <div>
                                            <label for="meeting-new-email" class="mb-1 block text-sm font-semibold text-slate-700">Email</label>
                                            <input id="meeting-new-email" type="email" wire:model="new_email" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                            @error('new_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="meeting-new-phone" class="mb-1 block text-sm font-semibold text-slate-700">Phone</label>
                                            <input id="meeting-new-phone" type="text" wire:model="new_phone" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                            @error('new_phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                    <button type="button" wire:click="createProspectAndSchedule" class="mt-4 rounded-full bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                                        Add prospect & schedule
                                    </button>
                                </div>
                            @endif

                            @if (! $show_new_prospect_form)
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="meeting-format" class="mb-1 block text-sm font-semibold text-slate-700">Format</label>
                                        <select id="meeting-format" wire:model.live="meeting_format" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            @foreach ($meetingFormats as $format)
                                                <option value="{{ $format['value'] }}">{{ $format['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="meeting-when" class="mb-1 block text-sm font-semibold text-slate-700">When</label>
                                        <select id="meeting-when" wire:model="meeting_when" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="in_15">In 15 minutes</option>
                                            <option value="in_30">In 30 minutes</option>
                                            <option value="in_60">In 1 hour</option>
                                            <option value="today_14">Today at 2:00 PM</option>
                                            <option value="today_16">Today at 4:00 PM</option>
                                            <option value="tomorrow_10">Tomorrow at 10:00 AM</option>
                                            <option value="tomorrow_14">Tomorrow at 2:00 PM</option>
                                            <option value="next_week">Next Monday at 10:00 AM</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="meeting-duration" class="mb-1 block text-sm font-semibold text-slate-700">Duration (minutes)</label>
                                        <input id="meeting-duration" type="number" min="15" max="480" step="15" wire:model="duration_minutes" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    </div>
                                    <div>
                                        <label for="meeting-title" class="mb-1 block text-sm font-semibold text-slate-700">Title (optional)</label>
                                        <input id="meeting-title" type="text" wire:model="title" placeholder="Auto-generated from contact" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    </div>
                                </div>

                                @if ($meeting_format === 'in_person')
                                    <div>
                                        <label for="meeting-location" class="mb-1 block text-sm font-semibold text-slate-700">Location</label>
                                        <input id="meeting-location" type="text" wire:model="location" placeholder="Office, coffee shop, client home…" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                        @error('location') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                @else
                                    <div>
                                        <label for="meeting-link" class="mb-1 block text-sm font-semibold text-slate-700">Meeting link</label>
                                        <input id="meeting-link" type="url" wire:model="meeting_link" placeholder="https://zoom.us/j/…" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                        @error('meeting_link') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                @endif

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="meeting-recurrence" class="mb-1 block text-sm font-semibold text-slate-700">Repeat</label>
                                        <select id="meeting-recurrence" wire:model.live="recurrence" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            @foreach ($recurrenceOptions as $option)
                                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @if ($recurrence !== 'none')
                                        <div>
                                            <label for="meeting-recurrence-count" class="mb-1 block text-sm font-semibold text-slate-700">Occurrences</label>
                                            <select id="meeting-recurrence-count" wire:model="recurrence_count" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                @foreach ($recurrenceCounts as $count)
                                                    <option value="{{ $count }}">{{ $count }} meetings</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                </div>

                                <div>
                                    <label for="meeting-notes" class="mb-1 block text-sm font-semibold text-slate-700">Notes</label>
                                    <textarea id="meeting-notes" wire:model="notes" rows="2" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Agenda, prep notes, etc."></textarea>
                                </div>

                                <div class="flex justify-end pt-2">
                                    <button type="submit" class="rounded-full bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                                        Schedule meeting
                                    </button>
                                </div>
                            @endif
                        </form>
                    </section>
                </div>
            </div>
        </div>
    @endif
</div>
