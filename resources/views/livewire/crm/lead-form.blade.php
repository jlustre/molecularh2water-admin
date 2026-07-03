<div class="p-4 sm:p-6 lg:p-8">
    @if ($isProspectForm)
        {{-- Prospect: compact single-column layout --}}
        <div class="mx-auto max-w-4xl">
            <div class="mb-5 flex items-center justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-teal-600">Prospect</p>
                    <h1 class="text-2xl font-bold text-slate-900">{{ $lead ? 'Edit' : 'Create' }} Prospect</h1>
                </div>
                <a
                    class="shrink-0 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm hover:bg-slate-50"
                    href="{{ $lead ? $this->profileUrl() : $this->listUrl() }}"
                >
                    {{ $lead ? '← Profile' : '← Back to prospect list' }}
                </a>
            </div>

            <form class="space-y-4" wire:submit="save">
                {{-- Contact --}}
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 bg-gradient-to-r from-teal-50 to-slate-100 px-4 py-2.5">
                        <h2 class="text-sm font-bold text-slate-900">Contact Information</h2>
                    </div>
                    <div class="space-y-3 bg-slate-100 p-4">
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <label class="mb-0.5 block text-xs font-semibold text-slate-600">First Name *</label>
                                <input class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" type="text" wire:model="first_name" />
                                @error('first_name') <p class="mt-0.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-0.5 block text-xs font-semibold text-slate-600">Last Name</label>
                                <input class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" type="text" wire:model="last_name" />
                            </div>
                            <div>
                                <label class="mb-0.5 block text-xs font-semibold text-slate-600">Email</label>
                                <input class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" type="email" wire:model="email" />
                                @error('email') <p class="mt-0.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-0.5 block text-xs font-semibold text-slate-600">Phone</label>
                                <input class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" type="text" wire:model="phone" />
                                @error('phone') <p class="mt-0.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-6">
                            <div class="sm:col-span-4">
                                <label class="mb-0.5 block text-xs font-semibold text-slate-600">Address</label>
                                <input class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" type="text" wire:model="address" />
                                @error('address') <p class="mt-0.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-0.5 block text-xs font-semibold text-slate-600">City</label>
                                <input class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" type="text" wire:model="city" />
                            </div>
                            <div>
                                <label class="mb-0.5 block text-xs font-semibold text-slate-600">State</label>
                                <input class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" type="text" wire:model="state" />
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <label class="mb-0.5 block text-xs font-semibold text-slate-600">Company</label>
                                <input class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" type="text" wire:model="company" />
                            </div>
                            <div>
                                <label class="mb-0.5 block text-xs font-semibold text-slate-600">Occupation</label>
                                <input class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" type="text" wire:model="occupation" />
                                @error('occupation') <p class="mt-0.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-0.5 block text-xs font-semibold text-slate-600">Spouse Name</label>
                                <input class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" type="text" wire:model="spouse_name" />
                                @error('spouse_name') <p class="mt-0.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-0.5 block text-xs font-semibold text-slate-600">Spouse Occupation</label>
                                <input class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" type="text" wire:model="spouse_occupation" />
                                @error('spouse_occupation') <p class="mt-0.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-0.5 block text-xs font-semibold text-slate-600">Best Time to Contact</label>
                                <select class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" wire:model="best_time_to_contact">
                                    <option value="">Select a time</option>
                                    @foreach ($bestTimesToContact as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('best_time_to_contact') <p class="mt-0.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-0.5 block text-xs font-semibold text-slate-600">Notes</label>
                                <textarea
                                    class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                    placeholder="Add notes about this prospect..."
                                    rows="2"
                                    wire:model="message"
                                ></textarea>
                                @error('message') <p class="mt-0.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </section>

                {{-- CRM Details --}}
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 bg-gradient-to-r from-slate-100 to-slate-200/60 px-4 py-2.5">
                        <h2 class="text-sm font-bold text-slate-900">CRM Details</h2>
                        <p class="text-[11px] text-slate-500">Pipeline and engagement settings</p>
                    </div>
                    <div class="space-y-3 bg-slate-100 p-4">
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @if ($showBusinessLinePicker)
                                <div>
                                    <label class="mb-0.5 block text-xs font-semibold text-slate-600">Business Line</label>
                                    <select class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" wire:model="business_line">
                                        @foreach ($businessLines as $line)
                                            <option value="{{ $line->value }}">{{ $line->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div>
                                <label class="mb-0.5 block text-xs font-semibold text-slate-600">Lead Status</label>
                                <select class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" wire:model="status">
                                    @foreach ($statuses as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-0.5 block text-xs font-semibold text-slate-600">Temperature</label>
                                <select class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" wire:model="temperature">
                                    <option value="cold">Cold</option>
                                    <option value="warm">Warm</option>
                                    <option value="hot">Hot</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-0.5 block text-xs font-semibold text-slate-600">Score</label>
                                <input class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" max="100" min="0" type="number" wire:model="score" />
                            </div>
                            <div>
                                <label class="mb-0.5 block text-xs font-semibold text-slate-600">Source</label>
                                <select class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" wire:model="lead_source_id">
                                    <option value="">—</option>
                                    @foreach ($sources as $source)
                                        <option value="{{ $source->id }}">{{ $source->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if ($funnels->count() > 1)
                                <div>
                                    <label class="mb-0.5 block text-xs font-semibold text-slate-600">Pipeline</label>
                                    <select class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" wire:model.live="funnel_id">
                                        @foreach ($funnels as $funnel)
                                            <option value="{{ $funnel->id }}">{{ $funnel->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div>
                                <label class="mb-0.5 block text-xs font-semibold text-slate-600">Funnel Stage</label>
                                <select class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" wire:model.live="funnel_stage_id">
                                    <option value="">—</option>
                                    @foreach ($stages as $stage)
                                        <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if ($assignableUsers->isNotEmpty())
                                <div>
                                    <label class="mb-0.5 block text-xs font-semibold text-slate-600">Assigned To</label>
                                    <select class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" wire:model="assigned_user_id">
                                        <option value="">Unassigned</option>
                                        @foreach ($assignableUsers as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div>
                                <label class="mb-0.5 block text-xs font-semibold text-slate-600">Next Follow-Up</label>
                                <input class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" type="datetime-local" wire:model="next_follow_up_at" />
                            </div>
                            <div>
                                <label class="mb-0.5 block text-xs font-semibold text-slate-600">Last Contacted</label>
                                <input class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" type="datetime-local" wire:model="last_contacted_at" />
                            </div>
                            @if ($stageIsLost)
                                <div>
                                    <label class="mb-0.5 block text-xs font-semibold text-slate-600">Lost Reason</label>
                                    <select class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" wire:model.live="lost_reason_id">
                                        <option value="">Select a reason...</option>
                                        @foreach ($lostReasons as $reason)
                                            <option value="{{ $reason->id }}">{{ $reason->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('lost_reason_id') <p class="mt-0.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                                </div>
                                @if ($lost_reason_id && $lostReasons->firstWhere('id', $lost_reason_id)?->requires_detail)
                                    <div class="sm:col-span-2">
                                        <label class="mb-0.5 block text-xs font-semibold text-slate-600">Lost Reason Details</label>
                                        <textarea class="w-full rounded-lg border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" rows="2" wire:model="lost_reason_detail"></textarea>
                                        @error('lost_reason_detail') <p class="mt-0.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                            @endif
                        </div>
                        <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600 shadow-sm">
                            <input class="rounded border-slate-300 text-teal-600" type="checkbox" wire:model="consent_given" />
                            Consent given
                        </label>
                    </div>
                </section>

                {{-- Tags --}}
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 bg-gradient-to-r from-slate-100 to-slate-200/60 px-4 py-2.5">
                        <h2 class="text-sm font-bold text-slate-900">Profile Tags</h2>
                        <p class="text-[11px] text-slate-500">Select all that apply to this prospect.</p>
                    </div>
                    <div class="grid gap-2 bg-slate-100 p-4 sm:grid-cols-2 lg:grid-cols-3">
                        @forelse ($tags as $tag)
                            <label
                                class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm transition hover:border-teal-200 hover:bg-teal-50/50"
                                wire:key="prospect-tag-{{ $tag->id }}"
                            >
                                <input
                                    class="rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                                    type="checkbox"
                                    value="{{ $tag->id }}"
                                    wire:model.live="selectedTags"
                                />
                                <span>{{ $tag->name }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-slate-500">No profile tags configured.</p>
                        @endforelse
                    </div>
                    @error('selectedTags') <p class="px-4 pb-3 text-xs text-rose-600">{{ $message }}</p> @enderror
                    @error('selectedTags.*') <p class="px-4 pb-3 text-xs text-rose-600">{{ $message }}</p> @enderror
                </section>

                <div class="flex justify-end gap-3 pt-1">
                    <a
                        class="rounded-full border border-slate-200 bg-white px-5 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                        href="{{ $lead ? $this->profileUrl() : $this->listUrl() }}"
                    >
                        Cancel
                    </a>
                    <button
                        class="rounded-full bg-gradient-to-r from-teal-600 to-emerald-600 px-6 py-2 text-sm font-bold text-white shadow-sm hover:from-teal-700 hover:to-emerald-700"
                        type="submit"
                    >
                        {{ $lead ? 'Save Changes' : 'Create Prospect' }}
                    </button>
                </div>
            </form>
        </div>
    @else
        {{-- Lead / Client: original two-column layout --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-600">{{ $lifecycle->label() }}</p>
                <h1 class="mt-1 text-3xl font-bold text-slate-900">
                    {{ $lead ? 'Edit' : 'Create' }} {{ $lifecycle->label() }}
                </h1>
            </div>
            @if ($lead)
                <a class="text-sm font-semibold text-teal-700 hover:text-teal-900" href="{{ $this->profileUrl() }}">
                    ← Back to profile
                </a>
            @else
                <a class="text-sm font-semibold text-teal-700 hover:text-teal-900" href="{{ $this->listUrl() }}">
                    ← Back to {{ strtolower($lifecycle->label()) }} list
                </a>
            @endif
        </div>

        <form class="grid gap-6 lg:grid-cols-3" wire:submit="save">
            <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                <h2 class="text-lg font-bold text-slate-900">Contact Information</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">First Name *</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="first_name" />
                        @error('first_name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Last Name</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="last_name" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Email</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="email" wire:model="email" />
                        @error('email') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Phone</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="phone" />
                        @error('phone') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">City</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="city" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">State</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="state" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Country</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="country" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Company</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="company" />
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Interested In</label>
                    <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="interested_in" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Message</label>
                    <textarea class="w-full rounded-xl border-slate-200 shadow-sm" rows="4" wire:model="message"></textarea>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-900">CRM Details</h2>
                    <div class="mt-4 space-y-4">
                        @if ($showBusinessLinePicker)
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Business line</label>
                                <select class="w-full rounded-xl border-slate-200 shadow-sm" wire:model="business_line">
                                    @foreach ($businessLines as $line)
                                        <option value="{{ $line->value }}">{{ $line->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Lead Status</label>
                            <p class="mb-2 text-xs text-slate-500">Engagement level, separate from the funnel stage below.</p>
                            <select class="w-full rounded-xl border-slate-200 shadow-sm" wire:model="status">
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Temperature</label>
                            <select class="w-full rounded-xl border-slate-200 shadow-sm" wire:model="temperature">
                                <option value="cold">Cold</option>
                                <option value="warm">Warm</option>
                                <option value="hot">Hot</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Score</label>
                            <input class="w-full rounded-xl border-slate-200 shadow-sm" max="100" min="0" type="number" wire:model="score" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Source</label>
                            <select class="w-full rounded-xl border-slate-200 shadow-sm" wire:model="lead_source_id">
                                <option value="">—</option>
                                @foreach ($sources as $source)
                                    <option value="{{ $source->id }}">{{ $source->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($funnels->count() > 1)
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Pipeline</label>
                                <select class="w-full rounded-xl border-slate-200 shadow-sm" wire:model.live="funnel_id">
                                    @foreach ($funnels as $funnel)
                                        <option value="{{ $funnel->id }}">{{ $funnel->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Funnel Stage</label>
                            <select class="w-full rounded-xl border-slate-200 shadow-sm" wire:model.live="funnel_stage_id">
                                <option value="">—</option>
                                @foreach ($stages as $stage)
                                    <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($assignableUsers->isNotEmpty())
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Assigned To</label>
                                <select class="w-full rounded-xl border-slate-200 shadow-sm" wire:model="assigned_user_id">
                                    <option value="">Unassigned</option>
                                    @foreach ($assignableUsers as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Next Follow-Up</label>
                            <input class="w-full rounded-xl border-slate-200 shadow-sm" type="datetime-local" wire:model="next_follow_up_at" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Last Contacted</label>
                            <input class="w-full rounded-xl border-slate-200 shadow-sm" type="datetime-local" wire:model="last_contacted_at" />
                        </div>
                        @if ($stageIsLost)
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Lost Reason</label>
                                <select class="w-full rounded-xl border-slate-200 shadow-sm" wire:model.live="lost_reason_id">
                                    <option value="">Select a reason...</option>
                                    @foreach ($lostReasons as $reason)
                                        <option value="{{ $reason->id }}">{{ $reason->name }}</option>
                                    @endforeach
                                </select>
                                @error('lost_reason_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            @if ($lost_reason_id && $lostReasons->firstWhere('id', $lost_reason_id)?->requires_detail)
                                <div>
                                    <label class="mb-1 block text-sm font-semibold text-slate-700">Additional Details</label>
                                    <textarea class="w-full rounded-xl border-slate-200 shadow-sm" rows="3" wire:model="lost_reason_detail"></textarea>
                                    @error('lost_reason_detail') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                </div>
                            @endif
                        @endif
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" wire:model="consent_given" />
                            Consent given
                        </label>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-900">Tags</h2>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($tags as $tag)
                            <label class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-3 py-1.5 text-sm">
                                <input type="checkbox" value="{{ $tag->id }}" wire:model="selectedTags" />
                                {{ $tag->name }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <button
                    class="w-full rounded-full bg-gradient-to-r from-teal-600 to-emerald-600 px-6 py-3 text-sm font-bold text-white shadow-sm"
                    type="submit"
                >
                    {{ $lead ? 'Save Changes' : 'Create '.$lifecycle->label() }}
                </button>
            </div>
        </form>
    @endif
</div>
