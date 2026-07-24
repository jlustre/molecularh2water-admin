@extends('layouts.admin')

@section('content')
    <div class="space-y-4">
        @if (session('status'))
            <div class="rounded-md border border-teal-100 bg-teal-50 px-4 py-2.5 text-sm font-semibold text-teal-800">
                {{ session('status') }}
            </div>
        @endif

        <section class="overflow-hidden rounded-lg border border-teal-100 bg-gradient-to-br from-[#041f1e] via-[#062926] to-[#031a19] text-white shadow-lg">
            <div class="relative px-4 py-4 sm:px-6 sm:py-5">
                <div class="relative flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-100">{{ $formType->label() }}</p>
                        <h1 class="mt-2 text-2xl font-black sm:text-3xl">Website form submissions</h1>
                        <p class="mt-1 text-sm text-teal-50/75">{{ $formType->description() }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a
                            class="inline-flex shrink-0 items-center justify-center rounded-md border border-teal-300/40 bg-white/10 px-4 py-2.5 text-sm font-bold text-teal-50 transition hover:bg-white/15"
                            href="{{ $publicUrl }}"
                            rel="noreferrer"
                            target="_blank"
                        >
                            Open Public Form
                        </a>
                        @if (auth()->user()?->hasPermission('website-forms.manage'))
                            <a
                                class="inline-flex shrink-0 items-center justify-center rounded-md bg-teal-400 px-4 py-2.5 text-sm font-bold text-[#031a19] transition hover:bg-teal-300"
                                href="{{ route('admin.website-forms.create', $formType->routeKey()) }}"
                            >
                                Add Submission
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Total', 'value' => $totalSubmissions, 'meta' => 'All submissions'],
                ['label' => 'New', 'value' => $newSubmissions, 'meta' => 'Awaiting follow-up'],
                ['label' => 'This Month', 'value' => $thisMonthSubmissions, 'meta' => 'Current month'],
                ['label' => 'Contacted', 'value' => $contactedSubmissions, 'meta' => 'Marked contacted'],
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
        </div>

        <section class="rounded-lg border border-teal-100 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-700">Inbox</p>
                    <h2 class="mt-1 text-xl font-black text-slate-950">{{ $formType->label() }} records</h2>
                </div>

                <form action="{{ route('admin.website-forms.index', $formType->routeKey()) }}" class="flex flex-wrap gap-2" method="GET">
                    <input
                        class="rounded-full border border-teal-100 px-3.5 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400"
                        name="search"
                        placeholder="Search name, email, message..."
                        type="search"
                        value="{{ request('search') }}"
                    >
                    <select class="rounded-full border border-teal-100 px-3.5 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400" name="status">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $value => $label)
                            <option @selected(request('status') === $value) value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <select class="rounded-full border border-teal-100 px-3.5 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400" name="submitted">
                        <option value="">Any submitted date</option>
                        <option @selected(request('submitted') === '7_days') value="7_days">Last 7 days</option>
                        <option @selected(request('submitted') === '30_days') value="30_days">Last 30 days</option>
                        <option @selected(request('submitted') === '90_days') value="90_days">Last 90 days</option>
                    </select>
                    <select class="rounded-full border border-teal-100 px-3.5 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400" name="per_page">
                        @foreach ([10, 25, 50] as $size)
                            <option @selected((int) request('per_page', 10) === $size) value="{{ $size }}">{{ $size }} / page</option>
                        @endforeach
                    </select>
                    <button class="rounded-full bg-teal-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-teal-700" type="submit">
                        Filter
                    </button>
                </form>
            </div>

            <div class="mt-4 overflow-x-auto rounded-lg border border-slate-100">
                <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Contact</th>
                            <th class="px-4 py-3">Interest</th>
                            <th class="px-4 py-3">Preferred Time</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Submitted</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                        @forelse ($submissions as $submission)
                            <tr class="transition hover:bg-teal-50/50">
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-slate-900">{{ $submission->displayName() }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        @if ($submission->email)
                                            <a class="text-teal-700 hover:text-teal-800" href="mailto:{{ $submission->email }}">{{ $submission->email }}</a>
                                        @endif
                                        @if ($submission->email && $submission->phone) · @endif
                                        {{ $submission->phone }}
                                    </p>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="text-slate-800">{{ $submission->interested_in ?: '—' }}</p>
                                    @if ($submission->referrer_name)
                                        <p class="mt-1 text-xs text-slate-500">Referrer: {{ $submission->referrer_name }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-slate-600">{{ $submission->preferred_time ?: '—' }}</td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-bold text-teal-700">
                                        {{ $submission->status->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-slate-500">{{ $submission->created_at?->format('M j, Y g:i A') }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <a
                                            class="inline-flex items-center justify-center rounded-md border border-teal-100 bg-white px-3 py-1.5 text-xs font-bold text-teal-800 transition hover:bg-teal-50"
                                            href="{{ route('admin.website-forms.show', [$formType->routeKey(), $submission]) }}"
                                        >
                                            View
                                        </a>
                                        @if (auth()->user()?->hasPermission('website-forms.manage'))
                                            <a
                                                class="inline-flex items-center justify-center rounded-md border border-teal-100 bg-white px-3 py-1.5 text-xs font-bold text-teal-800 transition hover:bg-teal-50"
                                                href="{{ route('admin.website-forms.edit', [$formType->routeKey(), $submission]) }}"
                                            >
                                                Edit
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-12 text-center" colspan="6">
                                    <p class="text-base font-bold text-slate-900">No submissions yet</p>
                                    <p class="mt-1 text-sm text-slate-500">New public form entries will appear here automatically.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $submissions->links() }}
            </div>
        </section>
    </div>
@endsection
