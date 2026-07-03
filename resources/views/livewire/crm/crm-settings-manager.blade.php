<div class="p-4 sm:p-6 lg:p-8">
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    @error('item')
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $message }}</div>
    @enderror

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-600">Configuration</p>
            <h1 class="mt-1 text-3xl font-bold text-slate-900">CRM Settings</h1>
            <p class="mt-1 text-sm text-slate-500">Lead sources, lost reasons, tags, activity types, and team structure.</p>
        </div>
        <a
            class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
            href="{{ route(\App\Support\Crm\CrmRoutes::name('funnels.index')) }}"
        >
            Funnel Builder →
        </a>
    </div>

    <div class="mb-6 flex flex-wrap gap-2 border-b border-slate-200 pb-4">
        @foreach ([
            'sources' => 'Lead Sources',
            'lost-reasons' => 'Lost Reasons',
            'tags' => 'Tags',
            'activity-types' => 'Activity Types',
        ] as $key => $label)
            <button
                type="button"
                class="rounded-full px-4 py-2 text-sm font-semibold transition {{ $activeTab === $key ? 'bg-teal-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                wire:click="setTab('{{ $key }}')"
            >
                {{ $label }}
            </button>
        @endforeach
        @if ($canManageTeams)
            <button
                type="button"
                class="rounded-full px-4 py-2 text-sm font-semibold transition {{ $activeTab === 'teams' ? 'bg-teal-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                wire:click="setTab('teams')"
            >
                Teams
            </button>
        @endif
    </div>

    @if ($activeTab === 'sources')
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-1">
                <h2 class="text-lg font-bold text-slate-900">{{ $editingSourceId ? 'Edit Source' : 'Add Source' }}</h2>
                <form class="mt-4 space-y-4" wire:submit="saveSource">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Name</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="sourceName" />
                        @error('sourceName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Description</label>
                        <textarea class="w-full rounded-xl border-slate-200 shadow-sm" rows="3" wire:model="sourceDescription"></textarea>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="sourceIsActive" />
                        Active
                    </label>
                    <div class="flex gap-2">
                        <button class="rounded-full bg-teal-600 px-5 py-2 text-sm font-semibold text-white hover:bg-teal-700" type="submit">Save</button>
                        @if ($editingSourceId)
                            <button class="rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-600" type="button" wire:click="cancelEdit">Cancel</button>
                        @endif
                    </div>
                </form>
            </div>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-500">Name</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-500">Status</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($sources as $source)
                            <tr wire:key="source-{{ $source->id }}">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $source->name }}</td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-xs font-semibold',
                                        'bg-emerald-100 text-emerald-800' => $source->is_active,
                                        'bg-slate-100 text-slate-600' => ! $source->is_active,
                                    ])>{{ $source->is_active ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button class="font-semibold text-teal-600 hover:text-teal-800" type="button" wire:click="startEditSource({{ $source->id }})">Edit</button>
                                    <button class="ml-3 font-semibold text-rose-600 hover:text-rose-800" type="button" wire:click="deleteSource({{ $source->id }})">Delete</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($activeTab === 'lost-reasons')
        <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
            Use a single lost stage on the funnel board and record why the sale did not close here instead of creating multiple lost stages.
        </div>
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-1">
                <h2 class="text-lg font-bold text-slate-900">{{ $editingLostReasonId ? 'Edit Lost Reason' : 'Add Lost Reason' }}</h2>
                <form class="mt-4 space-y-4" wire:submit="saveLostReason">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Name</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="lostReasonName" />
                        @error('lostReasonName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Description</label>
                        <textarea class="w-full rounded-xl border-slate-200 shadow-sm" rows="3" wire:model="lostReasonDescription"></textarea>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="lostReasonRequiresDetail" />
                        Requires additional detail (e.g. Other)
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="lostReasonIsActive" />
                        Active
                    </label>
                    <div class="flex gap-2">
                        <button class="rounded-full bg-teal-600 px-5 py-2 text-sm font-semibold text-white hover:bg-teal-700" type="submit">Save</button>
                        @if ($editingLostReasonId)
                            <button class="rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-600" type="button" wire:click="cancelEdit">Cancel</button>
                        @endif
                    </div>
                </form>
            </div>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-500">Reason</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-500">Detail</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-500">Status</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($lostReasons as $reason)
                            <tr wire:key="lost-reason-{{ $reason->id }}">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $reason->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $reason->requires_detail ? 'Required' : '—' }}</td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-xs font-semibold',
                                        'bg-emerald-100 text-emerald-800' => $reason->is_active,
                                        'bg-slate-100 text-slate-600' => ! $reason->is_active,
                                    ])>{{ $reason->is_active ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button class="font-semibold text-teal-600 hover:text-teal-800" type="button" wire:click="startEditLostReason({{ $reason->id }})">Edit</button>
                                    <button class="ml-3 font-semibold text-rose-600 hover:text-rose-800" type="button" wire:click="deleteLostReason({{ $reason->id }})">Delete</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($activeTab === 'tags')
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-1">
                <h2 class="text-lg font-bold text-slate-900">{{ $editingTagId ? 'Edit Tag' : 'Add Tag' }}</h2>
                <form class="mt-4 space-y-4" wire:submit="saveTag">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Name</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="tagName" />
                        @error('tagName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Color</label>
                        <select class="w-full rounded-xl border-slate-200 shadow-sm" wire:model="tagColor">
                            @foreach ($stageColors as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button class="rounded-full bg-teal-600 px-5 py-2 text-sm font-semibold text-white hover:bg-teal-700" type="submit">Save</button>
                        @if ($editingTagId)
                            <button class="rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-600" type="button" wire:click="cancelEdit">Cancel</button>
                        @endif
                    </div>
                </form>
            </div>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-500">Tag</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-500">Color</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($tags as $tag)
                            <tr wire:key="tag-{{ $tag->id }}">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $tag->name }}</td>
                                <td class="px-4 py-3 text-slate-600 capitalize">{{ $stageColors[$tag->color] ?? $tag->color ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button class="font-semibold text-teal-600 hover:text-teal-800" type="button" wire:click="startEditTag({{ $tag->id }})">Edit</button>
                                    <button class="ml-3 font-semibold text-rose-600 hover:text-rose-800" type="button" wire:click="deleteTag({{ $tag->id }})">Delete</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($activeTab === 'activity-types')
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-1">
                <h2 class="text-lg font-bold text-slate-900">{{ $editingActivityTypeId ? 'Edit Type' : 'Add Type' }}</h2>
                <form class="mt-4 space-y-4" wire:submit="saveActivityType">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Name</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="activityTypeName" />
                        @error('activityTypeName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Icon (optional)</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" placeholder="phone" wire:model="activityTypeIcon" />
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="activityTypeIsActive" />
                        Active
                    </label>
                    <div class="flex gap-2">
                        <button class="rounded-full bg-teal-600 px-5 py-2 text-sm font-semibold text-white hover:bg-teal-700" type="submit">Save</button>
                        @if ($editingActivityTypeId)
                            <button class="rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-600" type="button" wire:click="cancelEdit">Cancel</button>
                        @endif
                    </div>
                </form>
            </div>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-500">Type</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-500">Icon</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-500">Status</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($activityTypes as $type)
                            <tr wire:key="activity-type-{{ $type->id }}">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $type->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $type->icon ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-xs font-semibold',
                                        'bg-emerald-100 text-emerald-800' => $type->is_active,
                                        'bg-slate-100 text-slate-600' => ! $type->is_active,
                                    ])>{{ $type->is_active ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button class="font-semibold text-teal-600 hover:text-teal-800" type="button" wire:click="startEditActivityType({{ $type->id }})">Edit</button>
                                    <button class="ml-3 font-semibold text-rose-600 hover:text-rose-800" type="button" wire:click="deleteActivityType({{ $type->id }})">Delete</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($activeTab === 'teams' && $canManageTeams)
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-1">
                <h2 class="text-lg font-bold text-slate-900">{{ $editingTeamId ? 'Edit Team' : 'Add Team' }}</h2>
                <form class="mt-4 space-y-4" wire:submit="saveTeam">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Name</label>
                        <input class="w-full rounded-xl border-slate-200 shadow-sm" type="text" wire:model="teamName" />
                        @error('teamName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Description</label>
                        <textarea class="w-full rounded-xl border-slate-200 shadow-sm" rows="3" wire:model="teamDescription"></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Manager</label>
                        <select class="w-full rounded-xl border-slate-200 shadow-sm" wire:model="teamManagerId">
                            <option value="">— None —</option>
                            @foreach ($assignableUsers as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <p class="mb-2 text-sm font-semibold text-slate-700">Members</p>
                        <div class="max-h-40 space-y-2 overflow-y-auto rounded-xl border border-slate-200 p-3">
                            @foreach ($assignableUsers as $user)
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" value="{{ $user->id }}" wire:model="teamMemberIds" />
                                    {{ $user->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="teamIsActive" />
                        Active
                    </label>
                    <div class="flex gap-2">
                        <button class="rounded-full bg-teal-600 px-5 py-2 text-sm font-semibold text-white hover:bg-teal-700" type="submit">Save</button>
                        @if ($editingTeamId)
                            <button class="rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-600" type="button" wire:click="cancelEdit">Cancel</button>
                        @endif
                    </div>
                </form>
            </div>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-500">Team</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-500">Manager</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-500">Members</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($teams as $team)
                            <tr wire:key="team-{{ $team->id }}">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $team->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $team->manager?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $team->users->count() }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button class="font-semibold text-teal-600 hover:text-teal-800" type="button" wire:click="startEditTeam({{ $team->id }})">Edit</button>
                                    <button class="ml-3 font-semibold text-rose-600 hover:text-rose-800" type="button" wire:click="deleteTeam({{ $team->id }})">Delete</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
