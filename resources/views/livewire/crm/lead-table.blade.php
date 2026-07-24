<div class="p-4 sm:p-6 lg:p-8">
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">{{ $lifecycle->label() }}</h2>
            <p class="mt-1 text-sm text-slate-500">Search, filter, and manage {{ strtolower($lifecycle->label()) }} records.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($this->canCreate())
                <a
                    class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-teal-600 to-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm"
                    href="{{ $this->createUrl() }}"
                >
                    Add {{ $lifecycle->label() }}
                </a>
            @endif
            @if (auth()->user()?->hasPermission('leads.export'))
                <a
                    class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                    href="{{ route(\App\Support\Crm\CrmRoutes::name('records.export'), ['lifecycle' => $lifecycle->value]) }}"
                >
                    Export CSV
                </a>
            @endif
        </div>
    </div>

    @if (auth()->user()?->hasPermission('leads.import'))
        <form
            action="{{ route(\App\Support\Crm\CrmRoutes::name('records.import')) }}"
            class="mb-4 flex flex-wrap items-end gap-3 rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-4"
            enctype="multipart/form-data"
            method="POST"
        >
            @csrf
            <input name="lifecycle" type="hidden" value="{{ $lifecycle->value }}" />
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Import CSV</label>
                <input
                    accept=".csv,text/csv"
                    class="mt-1 block text-sm text-slate-600"
                    name="file"
                    required
                    type="file"
                />
            </div>
            <button
                class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                type="submit"
            >
                Upload
            </button>
        </form>
    @endif

    <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-5">
        <input
            class="rounded-xl border-slate-200 shadow-sm focus:border-teal-500 focus:ring-teal-500"
            placeholder="Search name, email, phone..."
            type="search"
            wire:model.live.debounce.300ms="search"
        />
        <select class="rounded-xl border-slate-200 shadow-sm" wire:model.live="temperature">
            <option value="">All temperatures</option>
            <option value="cold">Cold</option>
            <option value="warm">Warm</option>
            <option value="hot">Hot</option>
        </select>
        <select class="rounded-xl border-slate-200 shadow-sm" wire:model.live="sourceId">
            <option value="">All sources</option>
            @foreach ($sources as $source)
                <option value="{{ $source->id }}">{{ $source->name }}</option>
            @endforeach
        </select>
        <select class="rounded-xl border-slate-200 shadow-sm" wire:model.live="status">
            <option value="">All lead statuses</option>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <select class="rounded-xl border-slate-200 shadow-sm" wire:model.live="assignedUserId">
            <option value="">All assignees</option>
            @foreach ($assignees as $assignee)
                <option value="{{ $assignee->id }}">{{ $assignee->name }}</option>
            @endforeach
        </select>
    </div>

    @if ($this->canBulkAssign() && count($selected) > 0)
        <div class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-teal-200 bg-teal-50 px-4 py-3">
            <span class="text-sm font-semibold text-teal-800">{{ count($selected) }} selected</span>
            <select class="rounded-xl border-slate-200 text-sm shadow-sm" wire:model="bulkAssigneeId">
                <option value="">Assign to…</option>
                @foreach ($assignees as $assignee)
                    <option value="{{ $assignee->id }}">{{ $assignee->name }}</option>
                @endforeach
            </select>
            <button
                class="rounded-full bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700"
                type="button"
                wire:click="bulkAssign"
            >
                Assign
            </button>
            @error('bulkAssigneeId')
                <p class="text-sm text-rose-600">{{ $message }}</p>
            @enderror
            @error('selected')
                <p class="text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    @if ($this->canBulkAssign())
                        <th class="w-10 px-4 py-3"></th>
                    @endif
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Contact</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Source</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Stage</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Temperature</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Lead Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Assigned</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Next Follow-Up</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($leads as $lead)
                    <tr class="hover:bg-slate-50/80" wire:key="lead-{{ $lead->id }}">
                        @if ($this->canBulkAssign())
                            <td class="px-4 py-3">
                                <input
                                    class="rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                                    type="checkbox"
                                    value="{{ $lead->id }}"
                                    wire:model.live="selected"
                                />
                            </td>
                        @endif
                        <td class="px-4 py-3">
                            <a class="font-semibold text-teal-700 hover:text-teal-900" href="{{ $this->showUrl($lead) }}">
                                {{ $lead->fullName() }}
                            </a>
                            <p class="text-xs text-slate-500">{{ $lead->email ?? $lead->phone ?? '—' }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $lead->source?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $lead->stage?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize',
                                'bg-slate-100 text-slate-700' => $lead->temperature?->value === 'cold',
                                'bg-amber-100 text-amber-800' => $lead->temperature?->value === 'warm',
                                'bg-rose-100 text-rose-800' => $lead->temperature?->value === 'hot',
                            ])>
                                {{ $lead->temperature?->label() ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $lead->status?->label() ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $lead->assignedUser?->name ?? 'Unassigned' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $lead->next_follow_up_at?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            @can('view', $lead)
                                <a class="font-semibold text-teal-700 hover:text-teal-900" href="{{ $this->showUrl($lead) }}">View</a>
                            @endcan
                            <a @class([
                                'font-semibold text-teal-700 hover:text-teal-900',
                                'ml-3' => auth()->user()?->can('view', $lead),
                            ]) href="{{ $this->editUrl($lead) }}">Edit</a>
                            @if ($this->canConvertLeadToProspect($lead))
                                <button
                                    class="ml-3 font-semibold text-cyan-700 hover:text-cyan-900"
                                    type="button"
                                    wire:click="convertToProspect({{ $lead->id }})"
                                    wire:confirm="Convert this lead to a prospect?"
                                >
                                    Convert to Prospect
                                </button>
                            @endif
                            @if (auth()->user()?->can('delete', $lead))
                                <button
                                    class="ml-3 font-semibold text-rose-600 hover:text-rose-800"
                                    type="button"
                                    wire:click="deleteLead({{ $lead->id }})"
                                    wire:confirm="Delete this record?"
                                >
                                    Delete
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-10 text-center text-sm text-slate-500" colspan="{{ $this->canBulkAssign() ? 9 : 8 }}">
                            No {{ strtolower($lifecycle->label()) }} records yet. Import or create one to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $leads->links() }}
    </div>
</div>
