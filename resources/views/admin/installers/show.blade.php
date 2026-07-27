@extends('layouts.admin')

@php
    $installerStatusClasses = [
        'active' => 'bg-emerald-50 text-emerald-700',
        'archived' => 'bg-slate-100 text-slate-600',
    ];
    $jobStatusClasses = [
        'scheduled' => 'bg-cyan-50 text-cyan-700',
        'completed' => 'bg-emerald-50 text-emerald-700',
        'cancelled' => 'bg-rose-50 text-rose-700',
        'rescheduled' => 'bg-amber-50 text-amber-700',
    ];
@endphp

@section('content')
    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-md border border-teal-100 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
                {{ session('status') }}
            </div>
        @endif

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Installer Management</p>
                    <div class="mt-2 flex flex-wrap items-center gap-3">
                        <h1 class="text-3xl font-black tracking-normal text-slate-950">{{ $installer->name }}</h1>
                        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $installerStatusClasses[$installer->status->value] ?? $installerStatusClasses['active'] }}">
                            {{ $installer->status->label() }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-slate-500">
                        Installation history for this installer. They do not need an app membership.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.installers.index') }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-4 py-2.5 text-sm font-bold text-teal-800 transition hover:bg-teal-50">
                        Back to list
                    </a>
                    @if (auth()->user()?->hasPermission('installers.manage'))
                        <a href="{{ route('admin.installers.edit', $installer) }}" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-4 py-2.5 text-sm font-bold text-[#031a19] transition hover:bg-teal-300">
                            Edit Installer
                        </a>
                        @if ($installer->isArchived())
                            <form method="POST" action="{{ route('admin.installers.restore', $installer) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-4 py-2.5 text-sm font-bold text-teal-800 transition hover:bg-teal-50">
                                    Restore
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.installers.archive', $installer) }}" onsubmit="return confirm('Archive this installer?');">
                                @csrf
                                <button type="submit" class="inline-flex items-center justify-center rounded-md border border-amber-200 bg-white px-4 py-2.5 text-sm font-bold text-amber-800 transition hover:bg-amber-50">
                                    Archive
                                </button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('admin.installers.destroy', $installer) }}" onsubmit="return confirm('Delete this installer and all installation history? This cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center justify-center rounded-md border border-red-200 bg-white px-4 py-2.5 text-sm font-bold text-red-700 transition hover:bg-red-50">
                                Delete
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Phone</p>
                    <p class="mt-1 font-semibold text-slate-900">{{ $installer->phone ?: '—' }}</p>
                </div>
                <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Email</p>
                    <p class="mt-1 break-all font-semibold text-slate-900">{{ $installer->email ?: '—' }}</p>
                </div>
                <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Company</p>
                    <p class="mt-1 font-semibold text-slate-900">{{ $installer->company ?: '—' }}</p>
                </div>
                <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Location</p>
                    <p class="mt-1 font-semibold text-slate-900">
                        {{ collect([$installer->city, $installer->state])->filter()->implode(', ') ?: '—' }}
                    </p>
                </div>
            </div>

            @if ($installer->notes)
                <div class="mt-4 rounded-lg border border-teal-100 bg-teal-50/50 p-4 text-sm text-slate-700">
                    {{ $installer->notes }}
                </div>
            @endif
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($installationStatuses as $value => $label)
                <div class="rounded-lg border border-teal-100 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold text-slate-500">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-black text-slate-950">{{ (int) ($statusCounts[$value] ?? 0) }}</p>
                </div>
            @endforeach
        </section>

        @if (auth()->user()?->hasPermission('installers.manage'))
            <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-700">Add Installation</p>
                <h2 class="mt-1 text-xl font-black text-slate-950">Log a job for this installer</h2>
                <form class="mt-5" method="POST" action="{{ route('admin.installers.installations.store', $installer) }}">
                    @include('admin.installers._installation_form', [
                        'prefix' => 'create_',
                        'installation' => $installation,
                        'installationStatuses' => $installationStatuses,
                    ])
                    <div class="mt-5 flex justify-end">
                        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-2.5 text-sm font-bold text-[#031a19] transition hover:bg-teal-300">
                            Add Installation Record
                        </button>
                    </div>
                </form>
            </section>
        @endif

        <section class="rounded-lg border border-teal-100 bg-white p-4 shadow-sm sm:p-6">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-700">History</p>
                    <h2 class="mt-1 text-xl font-black text-slate-950">Installation records</h2>
                </div>

                <form action="{{ route('admin.installers.show', $installer) }}" class="flex flex-wrap gap-2" method="GET">
                    <input class="rounded-full border border-teal-100 px-3.5 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400" name="history_search" placeholder="Search customer or address..." type="search" value="{{ request('history_search') }}">
                    <select class="rounded-full border border-teal-100 px-3.5 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400" name="history_status">
                        <option value="">Any status</option>
                        @foreach ($installationStatuses as $value => $label)
                            <option @selected(request('history_status') === $value) value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-full bg-teal-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-teal-700" type="submit">Filter</button>
                </form>
            </div>

            <div class="mt-5 space-y-4">
                @forelse ($installations as $record)
                    <article class="rounded-lg border border-slate-100 p-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $jobStatusClasses[$record->status->value] ?? $jobStatusClasses['scheduled'] }}">
                                        {{ $record->status->label() }}
                                    </span>
                                    <p class="font-semibold text-slate-900">
                                        {{ $record->customer_name ?: 'Unnamed customer' }}
                                    </p>
                                </div>
                                <p class="mt-2 text-sm text-slate-600">
                                    {{ $record->locationSummary() ?: 'No address on file' }}
                                </p>
                                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs font-semibold text-slate-500">
                                    <span>Phone: {{ $record->customer_phone ?: '—' }}</span>
                                    <span>Email: {{ $record->customer_email ?: '—' }}</span>
                                    <span>Scheduled: {{ $record->scheduled_at?->format('M j, Y g:i A') ?: '—' }}</span>
                                    @if ($record->completed_at)
                                        <span>Completed: {{ $record->completed_at->format('M j, Y g:i A') }}</span>
                                    @endif
                                    @if ($record->cancelled_at)
                                        <span>Cancelled: {{ $record->cancelled_at->format('M j, Y g:i A') }}</span>
                                    @endif
                                    @if ($record->rescheduled_at)
                                        <span>Rescheduled: {{ $record->rescheduled_at->format('M j, Y g:i A') }}</span>
                                    @endif
                                </div>
                                @if ($record->notes)
                                    <p class="mt-2 text-sm text-slate-600">{{ $record->notes }}</p>
                                @endif
                            </div>

                            @if (auth()->user()?->hasPermission('installers.manage'))
                                <form method="POST" action="{{ route('admin.installers.installations.destroy', [$installer, $record]) }}" onsubmit="return confirm('Delete this installation record?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center justify-center rounded-md border border-red-100 bg-white px-3 py-2 text-xs font-bold text-red-600 transition hover:bg-red-50">
                                        Delete record
                                    </button>
                                </form>
                            @endif
                        </div>

                        @if (auth()->user()?->hasPermission('installers.manage'))
                            <details class="mt-4 rounded-md border border-teal-100 bg-teal-50/40 p-3">
                                <summary class="cursor-pointer text-sm font-bold text-teal-800">Update status / details</summary>
                                <form class="mt-4" method="POST" action="{{ route('admin.installers.installations.update', [$installer, $record]) }}">
                                    @method('PUT')
                                    @include('admin.installers._installation_form', [
                                        'prefix' => 'edit_'.$record->id.'_',
                                        'installation' => $record,
                                        'installationStatuses' => $installationStatuses,
                                    ])
                                    <div class="mt-4 flex justify-end">
                                        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-teal-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-teal-700">
                                            Save Record
                                        </button>
                                    </div>
                                </form>
                            </details>
                        @endif
                    </article>
                @empty
                    <div class="rounded-lg border border-dashed border-teal-200 bg-teal-50 px-4 py-10 text-center">
                        <p class="font-bold text-slate-900">No installation history yet</p>
                        <p class="mt-1 text-sm text-slate-500">Add a scheduled, completed, cancelled, or rescheduled job above.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-5">
                {{ $installations->links() }}
            </div>
        </section>
    </div>
@endsection
