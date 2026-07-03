<section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 bg-gradient-to-r from-teal-50 to-slate-100 px-4 py-2.5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Demonstrations</h2>
                <p class="text-[11px] text-slate-500">Schedule and track home, office, Zoom, and event demos.</p>
            </div>
            @can('update', $lead)
                <button
                    class="rounded-full bg-teal-600 px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-teal-700"
                    type="button"
                    wire:click="toggleScheduleForm"
                >
                    {{ $showScheduleForm ? 'Cancel' : 'Schedule Demo' }}
                </button>
            @endcan
        </div>
    </div>

    <div class="space-y-4 bg-slate-100 p-4">
        @if ($showScheduleForm)
            <form class="grid gap-3 rounded-lg border border-white bg-white p-4 shadow-sm sm:grid-cols-2" wire:submit="scheduleDemo">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Demo Type</label>
                <select class="w-full rounded-lg border-slate-200 text-sm" wire:model="type">
                    @foreach ($demoTypes as $demoType)
                        <option value="{{ $demoType->value }}">{{ $demoType->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Scheduled At</label>
                <input class="w-full rounded-lg border-slate-200 text-sm" type="datetime-local" wire:model="scheduled_at" />
                @error('scheduled_at') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Duration (minutes)</label>
                <input class="w-full rounded-lg border-slate-200 text-sm" min="15" type="number" wire:model="duration_minutes" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Venue</label>
                <input class="w-full rounded-lg border-slate-200 text-sm" placeholder="Home address, office, Zoom link..." wire:model="venue" />
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</label>
                <textarea class="w-full rounded-lg border-slate-200 text-sm" rows="2" wire:model="notes"></textarea>
            </div>
            <div class="sm:col-span-2 flex justify-end">
                <button class="rounded-full bg-marine px-5 py-2 text-sm font-bold text-white hover:bg-teal-700" type="submit">
                    Save Demo
                </button>
            </div>
            </form>
        @endif

        <div class="space-y-3">
            @forelse ($demonstrations as $demo)
                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="text-sm font-bold text-slate-900">{{ $demo->type->label() }}</p>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $demo->scheduled_at->format('M j, Y g:i A') }}
                            @if ($demo->demonstrator)
                                · {{ $demo->demonstrator->name }}
                            @endif
                        </p>
                    </div>
                    <span class="rounded-full bg-cyan-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-cyan-800">
                        {{ $demo->status->label() }}
                    </span>
                </div>
                @if ($demo->venue)
                    <p class="mt-2 text-xs text-slate-600">{{ $demo->venue }}</p>
                @endif
                    @if ($demo->notes)
                        <p class="mt-2 text-sm text-slate-600">{{ $demo->notes }}</p>
                    @endif
                    @if ($demo->outcome)
                        <p class="mt-2 text-xs font-semibold text-teal-700">Outcome: {{ $demo->outcome->label() }}</p>
                    @endif
                    @can('update', $lead)
                        @if ($demo->status->value !== 'completed' && $completingDemoId !== $demo->id)
                            <button
                                class="mt-3 rounded-full border border-teal-200 bg-teal-50 px-3 py-1.5 text-xs font-semibold text-teal-700 hover:bg-teal-100"
                                type="button"
                                wire:click="startComplete({{ $demo->id }})"
                            >
                                Complete Demo
                            </button>
                        @endif
                    @endcan
                    @if ($completingDemoId === $demo->id)
                        <form class="mt-3 grid gap-3 rounded-lg border border-teal-100 bg-teal-50/50 p-3 sm:grid-cols-2" wire:submit="completeDemo">
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Outcome</label>
                                <select class="w-full rounded-lg border-slate-200 text-sm" wire:model="complete_outcome">
                                    @foreach ($demoOutcomes as $outcome)
                                        <option value="{{ $outcome->value }}">{{ $outcome->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-end">
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" wire:model="complete_attended" />
                                    Customer attended
                                </label>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Completion Notes</label>
                                <textarea class="w-full rounded-lg border-slate-200 text-sm" rows="2" wire:model="complete_notes"></textarea>
                            </div>
                            <div class="sm:col-span-2 flex justify-end gap-2">
                                <button class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600" type="button" wire:click="cancelComplete">Cancel</button>
                                <button class="rounded-full bg-teal-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-teal-700" type="submit">Save Outcome</button>
                            </div>
                        </form>
                    @endif
                </article>
            @empty
                <p class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-sm text-slate-500">
                    No demonstrations scheduled yet.
                </p>
            @endforelse
        </div>
    </div>
</section>
