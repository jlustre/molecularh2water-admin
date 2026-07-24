@extends('layouts.admin')

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
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-100">Email Mappings</p>
                        <h1 class="mt-3 text-3xl font-black tracking-normal sm:text-4xl">Notify the right people when forms are submitted.</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-teal-50/75">
                            Map one or more recipient emails to each public website form. Every active recipient for that form is notified with submission details and a link to the admin record.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        @if (auth()->user()?->hasPermission('email-mappings.manage'))
                            <a href="{{ route('admin.email-mappings.create') }}" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-3 text-sm font-bold text-[#031a19] transition hover:bg-teal-300">
                                Add Mapping
                            </a>
                        @endif
                        @if (auth()->user()?->isSuperAdmin())
                            <form method="POST" action="{{ route('admin.email-mappings.update-seeder') }}">
                                @csrf
                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-md border border-teal-200/30 bg-white/[0.08] px-5 py-3 text-sm font-bold text-white transition hover:bg-white/[0.12]" title="Developer tool">
                                    Update Seeder
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-3">
            @foreach ([
                ['label' => 'Total', 'value' => $totalCount, 'meta' => 'All mappings'],
                ['label' => 'Active', 'value' => $activeCount, 'meta' => 'Currently notified'],
                ['label' => 'Inactive', 'value' => $inactiveCount, 'meta' => 'Paused recipients'],
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
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-700">Recipient Map</p>
                    <h2 class="mt-1 text-xl font-black text-slate-950">Form notification recipients</h2>
                </div>

                <form action="{{ route('admin.email-mappings.index') }}" class="flex flex-wrap gap-2" method="GET">
                    <input
                        class="rounded-full border border-teal-100 px-3.5 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400"
                        name="search"
                        placeholder="Search email or name..."
                        type="search"
                        value="{{ request('search') }}"
                    >
                    <select class="rounded-full border border-teal-100 px-3.5 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400" name="form_key">
                        <option value="">All forms</option>
                        @foreach ($formOptions as $value => $label)
                            <option @selected(request('form_key') === $value) value="{{ $value }}">
                                {{ $label }} ({{ (int) ($formCounts[$value] ?? 0) }})
                            </option>
                        @endforeach
                    </select>
                    <select class="rounded-full border border-teal-100 px-3.5 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400" name="status">
                        <option value="">Any status</option>
                        <option @selected(request('status') === 'active') value="active">Active</option>
                        <option @selected(request('status') === 'inactive') value="inactive">Inactive</option>
                    </select>
                    <button class="rounded-full bg-teal-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-teal-700" type="submit">
                        Filter
                    </button>
                </form>
            </div>

            <div class="mt-5 overflow-x-auto rounded-lg border border-slate-100">
                <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-2.5">Recipient</th>
                            <th class="px-3 py-2.5">Form</th>
                            <th class="px-3 py-2.5">Status</th>
                            <th class="px-3 py-2.5">Notes</th>
                            <th class="px-3 py-2.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                        @forelse ($mappings as $mapping)
                            <tr class="hover:bg-teal-50/50">
                                <td class="px-3 py-3">
                                    <p class="font-semibold text-slate-900">{{ $mapping->name ?: 'Unnamed recipient' }}</p>
                                    <p class="text-xs font-medium text-teal-700">{{ $mapping->email }}</p>
                                </td>
                                <td class="px-3 py-3 text-slate-700">{{ $mapping->form_key->label() }}</td>
                                <td class="px-3 py-3">
                                    @if ($mapping->is_active)
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">Active</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-slate-500">{{ $mapping->notes ?: '—' }}</td>
                                <td class="px-3 py-3">
                                    <div class="flex justify-end gap-2">
                                        @if (auth()->user()?->hasPermission('email-mappings.manage'))
                                            <a
                                                class="inline-flex size-9 items-center justify-center rounded-md border border-teal-100 text-teal-700 hover:bg-teal-50"
                                                href="{{ route('admin.email-mappings.edit', $mapping) }}"
                                                title="Edit"
                                            >
                                                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                    <path d="m4 20 4.5-1 10-10a2.12 2.12 0 0 0-3-3l-10 10L4 20Z" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="m14 7 3 3" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </a>
                                            <form action="{{ route('admin.email-mappings.destroy', $mapping) }}" method="POST" onsubmit="return confirm('Delete this email mapping?');">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    class="inline-flex size-9 items-center justify-center rounded-md border border-red-100 text-red-600 hover:bg-red-50"
                                                    title="Delete"
                                                    type="submit"
                                                >
                                                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                        <path d="M5 7h14M10 11v6M14 11v6M8 7l1-3h6l1 3M7 7l1 13h8l1-13" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-3 py-10 text-center text-slate-500" colspan="5">
                                    <p class="font-bold text-slate-900">No email mappings yet</p>
                                    <p class="mt-1 text-sm">Add recipients for each form so the team is notified when customers submit.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $mappings->links() }}
            </div>
        </section>
    </div>
@endsection
