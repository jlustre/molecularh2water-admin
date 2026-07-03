<div class="p-4 sm:p-6 lg:p-8">
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-600">Funnel Builder</p>
            <h1 class="mt-1 text-3xl font-bold text-slate-900">Pipeline Stages</h1>
            <p class="mt-1 text-sm text-slate-500">Configure stage names, colors, order, and won/lost markers.</p>
        </div>
        <a
            class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
            href="{{ route(\App\Support\Crm\CrmRoutes::name('pipeline.index')) }}"
        >
            ← Back to board
        </a>
    </div>

    @if ($funnels->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center text-sm text-slate-600">
            No funnels found. Seed the CRM data to get started.
        </div>
    @else
        @if ($funnels->count() > 1)
            <div class="mb-6 max-w-sm">
                <label class="mb-1 block text-sm font-semibold text-slate-700">Funnel</label>
                <select class="w-full rounded-xl border-slate-200 shadow-sm" wire:model.live="funnelId">
                    @foreach ($funnels as $funnel)
                        <option value="{{ $funnel->id }}">{{ $funnel->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500">Create Pipeline</h2>
            <form class="mt-3 grid gap-3 sm:grid-cols-[1fr_1fr_auto]" wire:submit="createFunnel">
                <input class="rounded-xl border-slate-200 text-sm shadow-sm" placeholder="Pipeline name" wire:model="newFunnelName" />
                <input class="rounded-xl border-slate-200 text-sm shadow-sm" placeholder="Description (optional)" wire:model="newFunnelDescription" />
                <button class="rounded-full bg-teal-600 px-5 py-2 text-sm font-semibold text-white hover:bg-teal-700" type="submit">
                    Create
                </button>
            </form>
            @error('newFunnelName') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                @error('stage')
                    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $message }}</div>
                @enderror

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Stage</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Color</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Leads</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($stages as $stage)
                                <tr wire:key="stage-row-{{ $stage->id }}">
                                    @if ($editingStageId === $stage->id)
                                        <td class="px-4 py-3" colspan="5">
                                            <form class="grid gap-3 md:grid-cols-4" wire:submit="saveEdit">
                                                <input class="rounded-xl border-slate-200 text-sm shadow-sm" type="text" wire:model="editName" />
                                                <select class="rounded-xl border-slate-200 text-sm shadow-sm" wire:model="editColor">
                                                    @foreach ($stageColors as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="flex flex-wrap gap-3 text-sm">
                                                    <label class="flex items-center gap-2">
                                                        <input type="checkbox" wire:model="editIsWon" /> Won
                                                    </label>
                                                    <label class="flex items-center gap-2">
                                                        <input type="checkbox" wire:model="editIsLost" /> Lost
                                                    </label>
                                                </div>
                                                <div class="flex justify-end gap-2">
                                                    <button class="text-sm font-semibold text-slate-600" type="button" wire:click="cancelEdit">Cancel</button>
                                                    <button class="rounded-full bg-teal-600 px-4 py-2 text-sm font-semibold text-white" type="submit">Save</button>
                                                </div>
                                            </form>
                                        </td>
                                    @else
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <span class="h-2.5 w-2.5 rounded-full {{ $stage->panelClasses()['dot'] }}"></span>
                                                <span class="font-semibold text-slate-900">{{ $stage->name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm capitalize text-slate-600">{{ $stage->color ?? 'slate' }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-600">
                                            @if ($stage->is_won)
                                                Won
                                            @elseif ($stage->is_lost)
                                                Lost
                                            @else
                                                Open
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ $stage->leads_count }}</td>
                                        <td class="px-4 py-3 text-right text-sm">
                                            <button class="font-semibold text-slate-600 hover:text-slate-900" type="button" wire:click="moveStage({{ $stage->id }}, 'up')">↑</button>
                                            <button class="ml-2 font-semibold text-slate-600 hover:text-slate-900" type="button" wire:click="moveStage({{ $stage->id }}, 'down')">↓</button>
                                            <button class="ml-3 font-semibold text-teal-700 hover:text-teal-900" type="button" wire:click="startEdit({{ $stage->id }})">Edit</button>
                                            <button
                                                class="ml-3 font-semibold text-rose-600 hover:text-rose-800"
                                                type="button"
                                                wire:click="deleteStage({{ $stage->id }})"
                                                wire:confirm="Delete this stage?"
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-8 text-center text-sm text-slate-500" colspan="5">No stages yet. Add your first stage.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Add Stage</h2>
                <form class="mt-4 space-y-4" wire:submit="addStage">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Name</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="newStageName" />
                        @error('newStageName') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Color</label>
                        <select class="w-full rounded-xl border-slate-200 shadow-sm" wire:model="newStageColor">
                            @foreach ($stageColors as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-2 text-sm text-slate-600">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" wire:model="newStageIsWon" /> Mark as won stage
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" wire:model="newStageIsLost" /> Mark as lost stage
                        </label>
                    </div>
                    <button
                        class="w-full rounded-full bg-gradient-to-r from-teal-600 to-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm"
                        type="submit"
                    >
                        Add Stage
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
