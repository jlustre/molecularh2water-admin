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
                <div class="absolute inset-0 opacity-[0.08] [background-image:linear-gradient(rgba(45,212,191,.85)_1px,transparent_1px),linear-gradient(90deg,rgba(45,212,191,.85)_1px,transparent_1px)] [background-size:36px_36px]"></div>
                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-3xl">
                        <p class="inline-flex items-center gap-2 rounded-full border border-teal-300/25 bg-white/[0.07] px-3 py-1 text-xs font-bold uppercase tracking-[0.22em] text-teal-100">
                            <span class="size-2 rounded-full bg-teal-300 shadow-[0_0_14px_rgba(45,212,191,0.9)]"></span>
                            Issue Reports
                        </p>
                        <h1 class="mt-5 text-3xl font-black tracking-normal sm:text-4xl">Track website and admin issues from any user.</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-teal-50/[0.72]">
                            Super-admins can review, update, assign, and close reports. Status changes automatically email the reporter.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ $publicUrl }}" rel="noreferrer" target="_blank" class="inline-flex items-center justify-center rounded-md border border-teal-200/40 bg-white/10 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/15">
                            Open Public Page
                        </a>
                        <a href="{{ route('admin.issue-reports.create') }}" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-3 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
                            Log Issue
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <a href="{{ route('admin.issue-reports.index') }}" class="rounded-lg border border-teal-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md">
                <p class="text-sm font-semibold text-slate-500">All reports</p>
                <h2 class="mt-2 text-xl font-black text-slate-950">Total</h2>
                <span class="mt-4 inline-flex size-11 items-center justify-center rounded-md bg-teal-50 text-sm font-black text-teal-700">{{ $totalCount }}</span>
            </a>
            <a href="{{ route('admin.issue-reports.index', ['status' => 'new']) }}" class="rounded-lg border border-teal-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md">
                <p class="text-sm font-semibold text-slate-500">Needs review</p>
                <h2 class="mt-2 text-xl font-black text-slate-950">Open</h2>
                <span class="mt-4 inline-flex size-11 items-center justify-center rounded-md bg-amber-50 text-sm font-black text-amber-700">{{ $openCount }}</span>
            </a>
            @foreach (['new' => 'New', 'in_progress' => 'In Progress', 'resolved' => 'Resolved'] as $value => $label)
                @php $count = (int) ($statusCounts[$value] ?? 0); @endphp
                <a href="{{ route('admin.issue-reports.index', ['status' => $value]) }}" class="rounded-lg border border-teal-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md">
                    <p class="text-sm font-semibold text-slate-500">Filter by status</p>
                    <h2 class="mt-2 text-xl font-black text-slate-950">{{ $label }}</h2>
                    <span class="mt-4 inline-flex size-11 items-center justify-center rounded-md bg-teal-50 text-sm font-black text-teal-700">{{ $count }}</span>
                </a>
            @endforeach
        </section>

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('admin.issue-reports.index') }}" class="grid gap-2 lg:grid-cols-6">
                <select name="status" class="rounded-full border border-teal-100 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="site" class="rounded-full border border-teal-100 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400">
                    <option value="">All sites</option>
                    @foreach ($sites as $value => $label)
                        <option value="{{ $value }}" @selected(request('site') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="severity" class="rounded-full border border-teal-100 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400">
                    <option value="">All severities</option>
                    @foreach ($severities as $value => $label)
                        <option value="{{ $value }}" @selected(request('severity') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="category" class="rounded-full border border-teal-100 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400">
                    <option value="">All categories</option>
                    @foreach ($categories as $value => $label)
                        <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <input name="search" type="search" value="{{ request('search') }}" class="rounded-full border border-teal-100 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-teal-400 focus:ring-teal-400 lg:col-span-1" placeholder="Search reports...">
                <button type="submit" class="rounded-full bg-teal-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-teal-700">Filter</button>
            </form>

            <div class="mt-6 overflow-x-auto rounded-lg border border-slate-100">
                <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Reference</th>
                            <th class="px-4 py-3">Issue</th>
                            <th class="px-4 py-3">Reporter</th>
                            <th class="px-4 py-3">Site</th>
                            <th class="px-4 py-3">Severity</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($reports as $report)
                            <tr>
                                <td class="px-4 py-4 font-mono text-xs font-bold text-teal-800">{{ $report->reference_code }}</td>
                                <td class="px-4 py-4">
                                    <p class="font-bold text-slate-950">{{ $report->title }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $report->category->label() }} · {{ $report->created_at?->format('M j, Y g:i A') }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-slate-900">{{ $report->reporter_name }}</p>
                                    <p class="text-xs text-slate-500">{{ $report->reporter_email }}</p>
                                </td>
                                <td class="px-4 py-4 text-slate-700">{{ $report->site->label() }}</td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $report->severity->badgeClasses() }}">{{ $report->severity->label() }}</span>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $report->status->badgeClasses() }}">{{ $report->status->label() }}</span>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <a href="{{ route('admin.issue-reports.show', $report) }}" class="font-bold text-teal-700 hover:text-teal-800">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-slate-500">No issue reports match these filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $reports->links() }}</div>
        </section>
    </div>
@endsection
