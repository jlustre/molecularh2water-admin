<div>
    @if ($show)
        <div class="shell-modal-overlay fixed inset-0 flex items-center justify-center overflow-y-auto px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="demos-title">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative mx-auto w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-violet-700">Quick Action</p>
                        <h2 id="demos-title" class="mt-1 text-xl font-black text-slate-950">Demos</h2>
                        <p class="mt-1 text-sm text-slate-500">Synced with your CRM calendar · sorted by soonest demo first.</p>
                    </div>
                    <button type="button" wire:click="close" class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                @if (session('demo_status'))
                    <div class="mx-5 mt-4 rounded-lg border border-violet-100 bg-violet-50 px-4 py-3 text-sm font-medium text-violet-800 sm:mx-6">
                        {{ session('demo_status') }}
                    </div>
                @endif

                <div class="max-h-[70vh] overflow-y-auto px-5 py-5 sm:px-6">
                    <section>
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h3 class="text-sm font-bold text-slate-900">Upcoming demos</h3>
                            <a href="{{ route(\App\Support\Crm\CrmRoutes::name('calendar.index')) }}" class="text-xs font-semibold text-violet-700 hover:text-violet-900">
                                Open Calendar
                            </a>
                        </div>

                        <ul class="space-y-2">
                            @forelse ($upcomingDemos as $demo)
                                <li class="flex items-start gap-3 rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                                    <div class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-violet-100 text-violet-700">
                                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                    </div>
                                    <div class="min-w-0 flex-1 space-y-0.5">
                                        <p class="truncate text-sm font-semibold text-slate-900">
                                            {{ $demo->lead?->fullName() ?? 'Contact' }}
                                            <span class="font-medium text-violet-700">· {{ $demo->type->label() }}</span>
                                        </p>
                                        <p class="truncate text-xs text-slate-500">
                                            {{ $demo->scheduled_at?->format('M j · g:i A') }}
                                            @if ($demo->venue)
                                                · {{ $demo->venue }}
                                            @endif
                                        </p>
                                    </div>
                                </li>
                            @empty
                                <li class="rounded-xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">
                                    No demos scheduled yet.
                                </li>
                            @endforelse
                        </ul>
                    </section>

                    <div class="my-6 border-t border-slate-200"></div>

                    <section>
                        <h3 class="text-sm font-bold text-slate-900">Schedule a demo</h3>
                        <p class="mt-1 text-xs text-slate-500">Pick a contact, demo type, and time — it syncs to your CRM calendar.</p>

                        <form class="mt-4 space-y-4" wire:submit="schedule">
                            <div class="relative">
                                <label for="demo-contact-search" class="mb-1 block text-sm font-semibold text-slate-700">
                                    Search contact
                                </label>
                                <input
                                    id="demo-contact-search"
                                    type="search"
                                    wire:model.live.debounce.300ms="contact_search"
                                    placeholder="Type at least 3 characters…"
                                    class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                    @disabled($show_new_prospect_form)
                                    autocomplete="off"
                                />
                                @if ($lead_id)
                                    <div class="mt-2 flex items-center justify-between gap-2 rounded-lg border border-violet-200 bg-violet-50 px-3 py-2">
                                        <p class="text-sm font-semibold text-violet-900">{{ $contact_search }}</p>
                                        <button type="button" wire:click="clearContact" class="text-xs font-semibold text-violet-700 hover:text-violet-900">
                                            Change
                                        </button>
                                    </div>
                                @endif
                                @error('contact_search') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                                @if ($showContactResults)
                                    <ul class="absolute z-10 mt-1 max-h-48 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                                        @forelse ($contactResults as $lead)
                                            <li>
                                                <button
                                                    type="button"
                                                    wire:click="selectContact({{ $lead->id }})"
                                                    class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-violet-50"
                                                >
                                                    <span class="font-semibold text-slate-900">{{ $lead->fullName() }}</span>
                                                    <span @class([
                                                        'shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide',
                                                        'bg-violet-100 text-violet-800' => $lead->lifecycle?->value === 'prospect',
                                                        'bg-emerald-100 text-emerald-800' => $lead->lifecycle?->value === 'client',
                                                    ])>
                                                        {{ $lead->lifecycle?->value === 'client' ? 'Customer' : 'Prospect' }}
                                                    </span>
                                                </button>
                                            </li>
                                        @empty
                                            <li class="px-3 py-2 text-xs text-slate-500">No matching prospects or customers. Keep typing to use a new name.</li>
                                        @endforelse
                                    </ul>
                                @endif
                            </div>

                            @if ($show_add_prospect_prompt)
                                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                                    <p class="text-sm font-semibold text-amber-950">
                                        “{{ trim($contact_search) }}” is not in your prospect or customer list yet.
                                    </p>
                                    <p class="mt-1 text-sm text-amber-900">Do you want to add this name to your prospect list?</p>
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            wire:click="confirmAddProspect"
                                            class="rounded-full bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700"
                                        >
                                            Yes, add prospect
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="cancelAddProspect"
                                            class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                        >
                                            No, go back
                                        </button>
                                    </div>
                                </div>
                            @endif

                            @if ($show_new_prospect_form)
                                <div class="rounded-xl border border-violet-200 bg-violet-50/60 p-4">
                                    <h4 class="text-sm font-bold text-slate-900">New prospect details</h4>
                                    <p class="mt-1 text-xs text-slate-600">Required fields to add this contact before scheduling the demo.</p>
                                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label for="demo-new-first-name" class="mb-1 block text-sm font-semibold text-slate-700">First name</label>
                                            <input id="demo-new-first-name" type="text" wire:model="new_first_name" class="block w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500" required />
                                            @error('new_first_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="demo-new-last-name" class="mb-1 block text-sm font-semibold text-slate-700">Last name</label>
                                            <input id="demo-new-last-name" type="text" wire:model="new_last_name" class="block w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500" />
                                            @error('new_last_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="demo-new-email" class="mb-1 block text-sm font-semibold text-slate-700">Email</label>
                                            <input id="demo-new-email" type="email" wire:model="new_email" class="block w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500" />
                                            @error('new_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="demo-new-phone" class="mb-1 block text-sm font-semibold text-slate-700">Phone</label>
                                            <input id="demo-new-phone" type="tel" wire:model="new_phone" class="block w-full rounded-xl border-slate-200 bg-white text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500" />
                                            @error('new_phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div>
                                <label for="demo-contact-email" class="mb-1 block text-sm font-semibold text-slate-700">
                                    Contact email
                                </label>
                                <input
                                    id="demo-contact-email"
                                    type="email"
                                    wire:model="contact_email"
                                    placeholder="contact@example.com"
                                    class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                />
                                <p class="mt-1 text-xs text-slate-500">
                                    We will email the demo details automatically. Online demos include the meeting link from settings.
                                </p>
                                @error('contact_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="demo-type" class="mb-1 block text-sm font-semibold text-slate-700">Demo type</label>
                                <select id="demo-type" wire:model="demo_type" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500">
                                    @foreach ($demoTypes as $type)
                                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                    @endforeach
                                </select>
                                @error('demo_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="demo-when" class="mb-1 block text-sm font-semibold text-slate-700">When</label>
                                    <select id="demo-when" wire:model="demo_when" class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500">
                                        <option value="in_60">In 1 hour</option>
                                        <option value="today_14">Today at 2:00 PM</option>
                                        <option value="today_16">Today at 4:00 PM</option>
                                        <option value="tomorrow_10">Tomorrow at 10:00 AM</option>
                                        <option value="tomorrow_14">Tomorrow at 2:00 PM</option>
                                        <option value="next_week">Next Monday at 10:00 AM</option>
                                    </select>
                                    @error('demo_when') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="demo-duration" class="mb-1 block text-sm font-semibold text-slate-700">Duration (minutes)</label>
                                    <input
                                        id="demo-duration"
                                        type="number"
                                        min="15"
                                        max="480"
                                        step="15"
                                        wire:model="duration_minutes"
                                        class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                    />
                                    @error('duration_minutes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div>
                                <label for="demo-venue" class="mb-1 block text-sm font-semibold text-slate-700">
                                    Venue <span class="font-normal text-slate-400">(optional)</span>
                                </label>
                                <input
                                    id="demo-venue"
                                    type="text"
                                    wire:model="venue"
                                    placeholder="Home address, office, Zoom link…"
                                    class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                />
                                @error('venue') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="demo-notes" class="mb-1 block text-sm font-semibold text-slate-700">
                                    Notes <span class="font-normal text-slate-400">(optional)</span>
                                </label>
                                <textarea
                                    id="demo-notes"
                                    wire:model="notes"
                                    rows="3"
                                    placeholder="Products to highlight, guest count, prep notes…"
                                    class="block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500"
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
                                        Schedule demo
                                    </x-primary-button>
                                @endif
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    @endif
</div>
