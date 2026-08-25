@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        @if (session('status'))
            <div class="rounded-md border border-teal-100 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
                {{ session('status') }}
            </div>
        @endif

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">{{ $report->reference_code }}</p>
                    <h1 class="mt-2 text-3xl font-black tracking-normal text-slate-950">{{ $report->title }}</h1>
                    <p class="mt-3 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $report->status->badgeClasses() }}">{{ $report->status->label() }}</span>
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $report->severity->badgeClasses() }}">{{ $report->severity->label() }}</span>
                        <span>{{ $report->site->label() }}</span>
                        <span>·</span>
                        <span>{{ $report->category->label() }}</span>
                        <span>·</span>
                        <span>{{ $report->source->label() }}</span>
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.issue-reports.edit', $report) }}" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-2.5 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
                        Edit Report
                    </a>
                    <form method="POST" action="{{ route('admin.issue-reports.destroy', $report) }}" onsubmit="return confirm('Delete this issue report? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center justify-center rounded-md border border-red-200 bg-white px-5 py-2.5 text-sm font-bold text-red-700 transition hover:bg-red-50">
                            Delete
                        </button>
                    </form>
                    <a href="{{ route('admin.issue-reports.index') }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-5 py-2.5 text-sm font-bold text-teal-800 transition hover:bg-teal-50">
                        Back To List
                    </a>
                </div>
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Reporter</h2>
                <dl class="mt-5 space-y-4 text-sm">
                    <div>
                        <dt class="font-semibold text-slate-500">Name</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $report->reporter_name }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Email</dt>
                        <dd class="mt-1">
                            <a class="font-semibold text-teal-700 hover:text-teal-800" href="mailto:{{ $report->reporter_email }}">{{ $report->reporter_email }}</a>
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Phone</dt>
                        <dd class="mt-1 text-slate-900">{{ $report->reporter_phone ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Linked account</dt>
                        <dd class="mt-1 text-slate-900">{{ $report->reporter?->name ?? 'Guest / public reporter' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Last emailed</dt>
                        <dd class="mt-1 text-slate-900">{{ $report->last_reporter_notified_at?->format('M j, Y g:i A') ?: '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Triage</h2>
                <dl class="mt-5 space-y-4 text-sm">
                    <div>
                        <dt class="font-semibold text-slate-500">Assigned to</dt>
                        <dd class="mt-1 text-slate-900">{{ $report->assignee?->name ?? 'Unassigned' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Page URL</dt>
                        <dd class="mt-1 break-all text-slate-900">
                            @if ($report->page_url)
                                <a class="font-semibold text-teal-700 hover:text-teal-800" href="{{ $report->page_url }}" rel="noreferrer" target="_blank">{{ $report->page_url }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Browser</dt>
                        <dd class="mt-1 text-slate-900">{{ $report->browser ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Device</dt>
                        <dd class="mt-1 text-slate-900">{{ $report->device ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Submitted</dt>
                        <dd class="mt-1 text-slate-900">{{ $report->created_at?->format('M j, Y g:i A') }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-black text-slate-950">Issue Details</h2>
            <div class="mt-5 space-y-5 text-sm leading-7 text-slate-700">
                <div>
                    <p class="font-semibold text-slate-500">Description</p>
                    <p class="mt-1 whitespace-pre-wrap">{{ $report->description }}</p>
                </div>
                <div class="grid gap-5 lg:grid-cols-3">
                    <div>
                        <p class="font-semibold text-slate-500">Steps to reproduce</p>
                        <p class="mt-1 whitespace-pre-wrap">{{ $report->steps_to_reproduce ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-500">Expected</p>
                        <p class="mt-1 whitespace-pre-wrap">{{ $report->expected_behavior ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-500">Actual</p>
                        <p class="mt-1 whitespace-pre-wrap">{{ $report->actual_behavior ?: '—' }}</p>
                    </div>
                </div>
            </div>
        </section>

        @if ($report->screenshotUrl())
            <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Screenshot</h2>
                <a class="mt-4 block overflow-hidden rounded-lg border border-slate-100" href="{{ $report->screenshotUrl() }}" rel="noreferrer" target="_blank">
                    <img src="{{ $report->screenshotUrl() }}" alt="Issue screenshot" class="max-h-[28rem] w-full object-contain bg-slate-50">
                </a>
            </section>
        @endif

        <section class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Internal notes</h2>
                <p class="mt-4 whitespace-pre-wrap text-sm leading-7 text-slate-700">{{ $report->admin_notes ?: 'No internal notes yet.' }}</p>
            </div>
            <div class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Resolution summary</h2>
                <p class="mt-2 text-xs font-medium text-slate-500">Included in status emails to the reporter.</p>
                <p class="mt-4 whitespace-pre-wrap text-sm leading-7 text-slate-700">{{ $report->resolution_summary ?: 'No resolution summary yet.' }}</p>
            </div>
        </section>

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-black text-slate-950">Status history</h2>
            <ol class="mt-5 space-y-4">
                @forelse ($report->statusUpdates as $update)
                    <li class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                        <p class="text-sm font-bold text-slate-900">
                            {{ $update->from_status?->label() ?? 'Submitted' }}
                            →
                            {{ $update->to_status->label() }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $update->created_at?->format('M j, Y g:i A') }}
                            ·
                            {{ $update->actor?->name ?? 'System' }}
                            ·
                            {{ $update->notified_reporter ? 'Reporter emailed' : 'Reporter not emailed' }}
                        </p>
                        @if (filled($update->note))
                            <p class="mt-2 text-sm leading-6 text-slate-700">{{ $update->note }}</p>
                        @endif
                    </li>
                @empty
                    <li class="text-sm text-slate-500">No status changes recorded yet.</li>
                @endforelse
            </ol>
        </section>
    </div>
@endsection
