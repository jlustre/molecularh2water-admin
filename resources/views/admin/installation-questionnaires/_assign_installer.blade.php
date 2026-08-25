@php
    $currentJob = $currentJob ?? $questionnaire->currentInstallerInstallation();
    $canManage = auth()->user()?->hasPermission('installation-questionnaires.manage');
    $canViewInstallers = auth()->user()?->hasPermission('installers.view');
    $jobStatusClasses = [
        'scheduled' => 'bg-cyan-50 text-cyan-700',
        'completed' => 'bg-emerald-50 text-emerald-700',
        'cancelled' => 'bg-rose-50 text-rose-700',
        'rescheduled' => 'bg-amber-50 text-amber-700',
    ];
@endphp

<section id="assign-installer" class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-700">Installer Assignment</p>
            <h2 class="mt-1 text-xl font-black text-slate-950">
                {{ $questionnaire->isAssigned() ? 'Assigned installer' : 'Assign an installer' }}
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                @if ($questionnaire->isAssigned())
                    This submission is on the selected installer's job list. They receive an email to accept or decline. Reassign if coverage needs to change.
                @else
                    Choose who will handle this install. Assignment emails the installer and creates a scheduled job from this questionnaire.
                @endif
            </p>
        </div>

        @if ($questionnaire->assignment_response)
            @include('admin.installation-questionnaires._response_badge', ['response' => $questionnaire->assignment_response])
        @elseif ($questionnaire->isAssigned())
            <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-emerald-700">
                Assigned
            </span>
        @else
            <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-amber-800">
                Unassigned
            </span>
        @endif
    </div>

    @if ($questionnaire->isAssigned() && $questionnaire->installer)
        <div class="mt-5 grid gap-4 lg:grid-cols-[1.15fr_0.85fr]">
            <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Installer</p>
                <p class="mt-1 text-lg font-black text-slate-950">{{ $questionnaire->installer->name }}</p>
                <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="font-semibold text-slate-500">Company</dt>
                        <dd class="mt-0.5 text-slate-900">{{ $questionnaire->installer->company ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Location</dt>
                        <dd class="mt-0.5 text-slate-900">{{ $questionnaire->installer->locationSummary() ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Phone</dt>
                        <dd class="mt-0.5 text-slate-900">{{ $questionnaire->installer->phone ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Email</dt>
                        <dd class="mt-0.5 break-all text-slate-900">
                            @if ($questionnaire->installer->email)
                                <a class="font-semibold text-teal-700 hover:text-teal-800" href="mailto:{{ $questionnaire->installer->email }}">{{ $questionnaire->installer->email }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>
                <p class="mt-3 text-xs font-semibold text-slate-500">
                    Assigned {{ $questionnaire->assigned_at?->format('M j, Y g:i A') ?: '—' }}
                    @if ($questionnaire->assignedBy)
                        by {{ $questionnaire->assignedBy->name }}
                    @endif
                </p>
                <div class="mt-3">
                    @include('admin.installation-questionnaires._response_badge', ['response' => $questionnaire->assignment_response])
                </div>
                @if ($questionnaire->assignment_notes)
                    <p class="mt-2 text-sm text-slate-600">{{ $questionnaire->assignment_notes }}</p>
                @endif
                @if ($canViewInstallers)
                    <a
                        class="mt-4 inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-4 py-2 text-sm font-bold text-teal-800 transition hover:bg-teal-50"
                        href="{{ route('admin.installers.show', $questionnaire->installer) }}"
                    >
                        Open installer record
                    </a>
                @endif
            </div>

            <div class="rounded-lg border border-slate-100 bg-white p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Installation job</p>
                @if ($currentJob)
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $jobStatusClasses[$currentJob->status->value] ?? $jobStatusClasses['scheduled'] }}">
                            {{ $currentJob->status->label() }}
                        </span>
                        @include('admin.installation-questionnaires._response_badge', ['response' => $currentJob->assignment_response, 'size' => 'sm'])
                    </div>
                    <p class="mt-3 text-sm text-slate-600">
                        Scheduled: {{ $currentJob->scheduled_at?->format('M j, Y g:i A') ?: 'Not set' }}
                    </p>
                    @if ($currentJob->notes)
                        <p class="mt-2 text-sm text-slate-600">{{ $currentJob->notes }}</p>
                    @endif
                @else
                    <p class="mt-2 text-sm text-slate-500">No open job is linked yet. Re-save the assignment to add one.</p>
                @endif
            </div>
        </div>
    @endif

    @if (! $questionnaire->isAssigned() && $questionnaire->assignment_response === \App\Enums\InstallerAssignmentResponse::Rejected)
        <div class="mt-5 rounded-lg border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-900">
            <p class="font-bold">Last installer declined this job</p>
            <p class="mt-1">
                {{ $questionnaire->assignment_rejection_reason?->label() ?: 'No reason given' }}
                @if ($questionnaire->assignment_responded_at)
                    · {{ $questionnaire->assignment_responded_at->format('M j, Y g:i A') }}
                @endif
            </p>
            @if ($questionnaire->assignment_rejection_notes)
                <p class="mt-1 text-rose-800">{{ $questionnaire->assignment_rejection_notes }}</p>
            @endif
        </div>
    @endif

    @if ($canManage)
        @if ($assignableInstallers->isEmpty())
            <div class="mt-5 rounded-lg border border-dashed border-teal-200 bg-teal-50 px-4 py-5 text-sm text-slate-600">
                <p class="font-bold text-slate-900">No active installers yet</p>
                <p class="mt-1">Add an installer first, then return here to assign this submission.</p>
                @if (auth()->user()?->hasPermission('installers.manage'))
                    <a class="mt-3 inline-flex font-bold text-teal-700 hover:text-teal-800" href="{{ route('admin.installers.create') }}">
                        Add installer
                    </a>
                @endif
            </div>
        @else
            <form
                class="mt-5 rounded-lg border border-teal-100 bg-teal-50/40 p-4"
                method="POST"
                action="{{ route('admin.installation-questionnaires.assign-installer', $questionnaire) }}"
            >
                @csrf
                <p class="text-sm font-bold text-slate-900">
                    {{ $questionnaire->isAssigned() ? 'Reassign or update this job' : 'Assign this submission' }}
                </p>
                <div class="mt-4 grid gap-4 lg:grid-cols-3">
                    <div class="lg:col-span-1">
                        <label class="block text-sm font-semibold text-slate-700" for="assign_installer_id">Installer *</label>
                        <select
                            class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                            id="assign_installer_id"
                            name="installer_id"
                            required
                        >
                            <option value="">Select an installer</option>
                            @foreach ($assignableInstallers as $installer)
                                @php
                                    $isNearby = \App\Support\UsStates::matches($installer->state, $questionnaire->state);
                                    $label = collect([
                                        $installer->name,
                                        $installer->company,
                                        $installer->locationSummary() ?: null,
                                    ])->filter()->implode(' — ');
                                @endphp
                                <option
                                    @selected((string) old('installer_id', $questionnaire->installer_id) === (string) $installer->id)
                                    value="{{ $installer->id }}"
                                >
                                    {{ $isNearby ? 'Nearby · ' : '' }}{{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('installer_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700" for="assign_scheduled_at">Scheduled at</label>
                        <input
                            class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                            id="assign_scheduled_at"
                            name="scheduled_at"
                            type="datetime-local"
                            value="{{ old('scheduled_at', optional($currentJob?->scheduled_at)->format('Y-m-d\TH:i')) }}"
                        >
                        @error('scheduled_at')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700" for="assign_notes">Assignment notes</label>
                        <input
                            class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                            id="assign_notes"
                            name="notes"
                            placeholder="Gate code, parking, timing..."
                            type="text"
                            value="{{ old('notes', $questionnaire->assignment_notes) }}"
                        >
                        @error('notes')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-end gap-2">
                    @if ($questionnaire->isAssigned())
                        <button
                            class="inline-flex items-center justify-center rounded-md border border-amber-200 bg-white px-4 py-2.5 text-sm font-bold text-amber-800 transition hover:bg-amber-50"
                            form="unassign-installer-form"
                            type="submit"
                        >
                            Unassign
                        </button>
                    @endif
                    <button class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-2.5 text-sm font-bold text-[#031a19] transition hover:bg-teal-300" type="submit">
                        {{ $questionnaire->isAssigned() ? 'Update assignment' : 'Assign installer' }}
                    </button>
                </div>
            </form>

            @if ($questionnaire->isAssigned())
                <form
                    id="unassign-installer-form"
                    method="POST"
                    action="{{ route('admin.installation-questionnaires.unassign-installer', $questionnaire) }}"
                    onsubmit="return confirm('Unassign this installer? Any open scheduled job will be cancelled.');"
                >
                    @csrf
                </form>
            @endif
        @endif
    @elseif (! $questionnaire->isAssigned())
        <p class="mt-5 text-sm text-slate-500">You can view assignments here. Assigning an installer requires manage access.</p>
    @endif
</section>
