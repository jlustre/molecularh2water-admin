@extends('layouts.admin')

@php
    $statusClasses = [
        'active' => 'bg-emerald-50 text-emerald-700',
        'archived' => 'bg-slate-100 text-slate-600',
    ];
@endphp

@section('content')
    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-md border border-teal-100 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
                {{ session('status') }}
            </div>
        @endif

        <section class="overflow-hidden rounded-lg border border-teal-100 bg-gradient-to-br from-[#041f1e] via-[#062926] to-[#031a19] text-white shadow-lg">
            <div class="relative px-6 py-7 sm:px-8">
                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-3xl">
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-100">Installer Management</p>
                        <h1 class="mt-3 text-3xl font-black tracking-normal sm:text-4xl">Track installers and their installation history.</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-teal-50/75">
                            Manage installer contacts who may not have app accounts. Open a record to review scheduled, completed, cancelled, and rescheduled jobs.
                        </p>
                    </div>

                    @if (auth()->user()?->hasPermission('installers.manage'))
                        <a href="{{ route('admin.installers.create') }}" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-3 text-sm font-bold text-[#031a19] transition hover:bg-teal-300">
                            Add Installer
                        </a>
                    @endif
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Total', 'value' => $totalCount, 'meta' => 'All installers'],
                ['label' => 'Active', 'value' => $activeCount, 'meta' => 'Available'],
                ['label' => 'Archived', 'value' => $archivedCount, 'meta' => 'Hidden from active work'],
                ['label' => 'Jobs', 'value' => $installationCount, 'meta' => 'History records'],
            ] as $card)
                <div class="rounded-lg border border-teal-100 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold text-slate-500">{{ $card['meta'] }}</p>
                    <div class="mt-2 flex items-end justify-between gap-2">
                        <p class="text-2xl font-black text-slate-950">{{ $card['value'] }}</p>
                        <span class="rounded-md bg-teal-50 px-2 py-1 text-[0.65rem] font-bold uppercase tracking-wide text-teal-700">
                            {{ $card['label'] }}
                        </span>
                    </div>
                </div>
            @endforeach
        </section>

        <section class="rounded-lg border border-teal-100 bg-white p-4 shadow-sm sm:p-6">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-700">Directory</p>
                    <h2 class="mt-1 text-xl font-black text-slate-950">Installer list</h2>
                </div>

                <form action="{{ route('admin.installers.index') }}" class="flex flex-wrap gap-2" method="GET">
                    <input class="rounded-full border border-teal-100 px-3.5 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400" name="search" placeholder="Search name, phone, email..." type="search" value="{{ request('search') }}">
                    <select class="rounded-full border border-teal-100 px-3.5 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400" name="status">
                        <option value="">Any status</option>
                        @foreach ($statuses as $value => $label)
                            <option @selected(request('status') === $value) value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <select class="rounded-full border border-teal-100 px-3.5 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400" name="per_page">
                        @foreach ([10, 15, 25, 50] as $size)
                            <option @selected((int) request('per_page', 15) === $size) value="{{ $size }}">{{ $size }} / page</option>
                        @endforeach
                    </select>
                    <button class="rounded-full bg-teal-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-teal-700" type="submit">Filter</button>
                </form>
            </div>

            <div class="mt-5 overflow-x-auto rounded-lg border border-slate-100">
                <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-2.5">Installer</th>
                            <th class="px-3 py-2.5">Phone</th>
                            <th class="px-3 py-2.5">Email</th>
                            <th class="px-3 py-2.5">Location</th>
                            <th class="px-3 py-2.5">Jobs</th>
                            <th class="px-3 py-2.5">Status</th>
                            <th class="px-3 py-2.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                        @forelse ($installers as $installer)
                            <tr class="transition hover:bg-teal-50/50">
                                <td class="px-3 py-3">
                                    <a class="font-semibold text-slate-900 hover:text-teal-700" href="{{ route('admin.installers.show', $installer) }}">
                                        {{ $installer->name }}
                                    </a>
                                    @if ($installer->company)
                                        <p class="text-xs text-slate-500">{{ $installer->company }}</p>
                                    @endif
                                </td>
                                <td class="px-3 py-3">{{ $installer->phone ?: '—' }}</td>
                                <td class="px-3 py-3">{{ $installer->email ?: '—' }}</td>
                                <td class="px-3 py-3">
                                    {{ collect([$installer->city, $installer->state])->filter()->implode(', ') ?: '—' }}
                                </td>
                                <td class="px-3 py-3">
                                    <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-bold text-teal-700">
                                        {{ $installer->installations_count }}
                                    </span>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClasses[$installer->status->value] ?? $statusClasses['active'] }}">
                                        {{ $installer->status->label() }}
                                    </span>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <a href="{{ route('admin.installers.show', $installer) }}" class="inline-flex size-9 items-center justify-center rounded-md border border-teal-100 bg-white text-teal-700 transition hover:bg-teal-50" title="View">
                                            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </a>
                                        @if (auth()->user()?->hasPermission('installers.manage'))
                                            <a href="{{ route('admin.installers.edit', $installer) }}" class="inline-flex size-9 items-center justify-center rounded-md border border-teal-100 bg-white text-teal-700 transition hover:bg-teal-50" title="Edit">
                                                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4 20 4.5-1 10-10a2.12 2.12 0 0 0-3-3l-10 10L4 20Z"/><path stroke-linecap="round" stroke-linejoin="round" d="m14 7 3 3"/></svg>
                                            </a>
                                            @if ($installer->isArchived())
                                                <form method="POST" action="{{ route('admin.installers.restore', $installer) }}">
                                                    @csrf
                                                    <button type="submit" class="inline-flex size-9 items-center justify-center rounded-md border border-teal-100 bg-white text-teal-700 transition hover:bg-teal-50" title="Restore">
                                                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12a9 9 0 1 0 3-6.7M3 4v5h5"/></svg>
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('admin.installers.archive', $installer) }}" onsubmit="return confirm('Archive this installer?');">
                                                    @csrf
                                                    <button type="submit" class="inline-flex size-9 items-center justify-center rounded-md border border-amber-100 bg-white text-amber-700 transition hover:bg-amber-50" title="Archive">
                                                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M9 11v6M15 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                                                    </button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('admin.installers.destroy', $installer) }}" onsubmit="return confirm('Delete this installer and all installation history? This cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex size-9 items-center justify-center rounded-md border border-red-100 bg-white text-red-600 transition hover:bg-red-50" title="Delete">
                                                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 7h14M10 11v6M14 11v6M8 7l1-3h6l1 3M7 7l1 13h8l1-13"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-12 text-center">
                                    <p class="text-base font-bold text-slate-900">No installers found</p>
                                    <p class="mt-1 text-sm text-slate-500">Add your first installer to start tracking jobs.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $installers->links() }}
            </div>
        </section>
    </div>
@endsection
