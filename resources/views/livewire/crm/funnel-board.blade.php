<div class="p-4 sm:p-6 lg:p-8">
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-600">Pipeline</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">Funnel Board</h2>
            <p class="mt-1 text-sm text-slate-500">Drag prospects between stages as they move from show booking to purchase.</p>
        </div>
        <div class="flex flex-wrap items-end gap-3">
            @if ($funnels->count() > 1)
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Funnel</label>
                    <select class="rounded-xl border-slate-200 text-sm shadow-sm" wire:model.live="funnelId">
                        @foreach ($funnels as $funnel)
                            <option value="{{ $funnel->id }}">{{ $funnel->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Lifecycle</label>
                <select class="rounded-xl border-slate-200 text-sm shadow-sm" wire:model.live="lifecycleFilter">
                    <option value="">All records</option>
                    <option value="lead">Leads</option>
                    <option value="prospect">Prospects</option>
                    <option value="client">Customers</option>
                </select>
            </div>
            @if (auth()->user()?->hasPermission('funnel.manage'))
                <a
                    class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                    href="{{ route(\App\Support\Crm\CrmRoutes::name('funnels.index')) }}"
                >
                    Manage Stages
                </a>
            @endif
        </div>
    </div>

    @if ($stages->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center">
            <p class="text-sm text-slate-600">
                No funnel configured yet. Run
                <code class="rounded bg-white px-1.5 py-0.5">php artisan db:seed --class=CrmSeeder</code>
                or configure stages in the funnel builder.
            </p>
        </div>
    @else
        <div
            class="flex gap-4 overflow-x-auto pb-4"
            x-data="{
                draggedLeadId: null,
                drop(stageId) {
                    if (this.draggedLeadId) {
                        $wire.requestMoveLead(this.draggedLeadId, stageId);
                        this.draggedLeadId = null;
                    }
                }
            }"
        >
            @foreach ($stages as $stage)
                @php($panel = $stage->panelClasses())
                <div
                    class="min-w-[300px] flex-1 rounded-2xl border {{ $panel['border'] }} {{ $panel['bg'] }}"
                    wire:key="stage-column-{{ $stage->id }}"
                    x-on:dragover.prevent
                    x-on:drop.prevent="drop({{ $stage->id }})"
                >
                    <div class="rounded-t-2xl border-b {{ $panel['border'] }} px-4 py-3 {{ $panel['header'] }}">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full {{ $panel['dot'] }}"></span>
                                <h3 class="font-semibold">{{ $stage->name }}</h3>
                                @if ($stage->is_won)
                                    <span class="rounded-full bg-emerald-200 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-800">Won</span>
                                @elseif ($stage->is_lost)
                                    <span class="rounded-full bg-rose-200 px-2 py-0.5 text-[10px] font-bold uppercase text-rose-800">Lost</span>
                                @endif
                            </div>
                            <span class="rounded-full bg-white/80 px-2 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-white/60">
                                {{ $stage->leads->count() }}
                            </span>
                        </div>
                    </div>
                    <div class="min-h-[120px] space-y-3 p-3">
                        @forelse ($stage->leads as $lead)
                            <div
                                class="cursor-grab rounded-xl border border-slate-200 bg-white p-3 shadow-sm active:cursor-grabbing"
                                draggable="true"
                                wire:key="board-lead-{{ $lead->id }}"
                                x-on:dragstart="draggedLeadId = {{ $lead->id }}"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <a
                                        class="font-semibold text-teal-700 hover:text-teal-900"
                                        href="{{ $this->leadProfileUrl($lead) }}"
                                    >
                                        {{ $lead->fullName() }}
                                    </a>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-slate-600">
                                        {{ $lead->lifecycle->value }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ $lead->email ?? $lead->phone ?? 'No contact' }}</p>
                                <div class="mt-3 flex items-center justify-between text-xs">
                                    <span @class([
                                        'rounded-full px-2 py-0.5 font-semibold capitalize',
                                        'bg-slate-100 text-slate-700' => $lead->temperature?->value === 'cold',
                                        'bg-amber-100 text-amber-800' => $lead->temperature?->value === 'warm',
                                        'bg-rose-100 text-rose-800' => $lead->temperature?->value === 'hot',
                                    ])>{{ $lead->temperature?->label() }}</span>
                                    <span class="text-slate-500">{{ $lead->assignedUser?->name ?? 'Unassigned' }}</span>
                                </div>
                                @if ($lead->next_follow_up_at)
                                    <p class="mt-2 text-xs text-teal-700">Follow-up {{ $lead->next_follow_up_at->format('M j') }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="rounded-xl border border-dashed border-slate-200 bg-white/60 px-3 py-6 text-center text-xs text-slate-400">
                                Drop records here
                            </p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($showLostModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">
                <h3 class="text-lg font-bold text-slate-900">Mark as lost</h3>
                <p class="mt-1 text-sm text-slate-500">Please provide a reason before moving this record to a lost stage.</p>
                <form class="mt-4 space-y-4" wire:submit="confirmLostMove">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Lost reason</label>
                        <select
                            class="w-full rounded-xl border-slate-200 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                            wire:model.live="lostReasonId"
                        >
                            <option value="">Select a reason...</option>
                            @foreach ($lostReasons as $reason)
                                <option value="{{ $reason->id }}">{{ $reason->name }}</option>
                            @endforeach
                        </select>
                        @error('lostReasonId') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    @if ($lostReasonId && $lostReasons->firstWhere('id', $lostReasonId)?->requires_detail)
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Additional details</label>
                            <textarea
                                class="w-full rounded-xl border-slate-200 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                placeholder="Describe why this record was lost..."
                                rows="3"
                                wire:model="lostReasonDetail"
                            ></textarea>
                            @error('lostReasonDetail') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    @endif
                    <div class="flex justify-end gap-2">
                        <button
                            class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            type="button"
                            wire:click="cancelLostMove"
                        >
                            Cancel
                        </button>
                        <button
                            class="rounded-full bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700"
                            type="submit"
                        >
                            Move to lost
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showCalendarSuggestion)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4" wire:keydown.escape.window="dismissCalendarSuggestion">
            <div class="w-full max-w-md rounded-2xl border border-cyan-200 bg-white p-6 shadow-xl">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-600">Next Step</p>
                <h3 class="mt-2 text-lg font-bold text-slate-900">Schedule a follow-up?</h3>
                <p class="mt-2 text-sm text-slate-600">
                    This stage often needs a calendar event: <strong>{{ $suggestionTitle }}</strong>
                </p>
                <div class="mt-5 flex justify-end gap-2">
                    <button
                        class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        type="button"
                        wire:click="dismissCalendarSuggestion"
                    >
                        Not now
                    </button>
                    @if ($this->calendarSuggestionUrl())
                        <a
                            class="rounded-full bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700"
                            href="{{ $this->calendarSuggestionUrl() }}"
                        >
                            Open Calendar
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
