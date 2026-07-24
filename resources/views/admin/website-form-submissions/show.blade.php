@extends('layouts.admin')

@php
    $statusClasses = [
        'new' => 'bg-sky-50 text-sky-700',
        'contacted' => 'bg-amber-50 text-amber-700',
        'scheduled' => 'bg-emerald-50 text-emerald-700',
        'closed' => 'bg-slate-100 text-slate-600',
        'spam' => 'bg-rose-50 text-rose-700',
    ];
@endphp

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        @if (session('status'))
            <div class="rounded-md border border-teal-100 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
                {{ session('status') }}
            </div>
        @endif

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">{{ $formType->label() }}</p>
                    <h1 class="mt-2 text-3xl font-black tracking-normal text-slate-950">{{ $submission->displayName() }}</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Submitted {{ $submission->created_at?->format('M j, Y g:i A') }}
                        ·
                        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClasses[$submission->status->value] ?? $statusClasses['new'] }}">
                            {{ $submission->status->label() }}
                        </span>
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    @if ($submission->prospect)
                        <a href="{{ route('admin.crm.prospects.show', $submission->prospect) }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-5 py-2.5 text-sm font-bold text-teal-800 transition hover:bg-teal-50">
                            Open CRM Prospect
                        </a>
                    @elseif (auth()->user()?->hasPermission('website-forms.manage') && (auth()->user()?->hasPermission('prospects.manage') || auth()->user()?->hasPermission('leads.create')))
                        <form method="POST" action="{{ route('admin.website-forms.convert-to-prospect', [$formType->routeKey(), $submission]) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center rounded-md border border-emerald-200 bg-emerald-50 px-5 py-2.5 text-sm font-bold text-emerald-800 transition hover:bg-emerald-100">
                                Create CRM Prospect
                            </button>
                        </form>
                    @endif
                    @if (auth()->user()?->hasPermission('website-forms.manage'))
                        <a href="{{ route('admin.website-forms.edit', [$formType->routeKey(), $submission]) }}" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-2.5 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
                            Edit Submission
                        </a>
                        <form method="POST" action="{{ route('admin.website-forms.destroy', [$formType->routeKey(), $submission]) }}" onsubmit="return confirm('Delete this submission?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center justify-center rounded-md border border-red-200 bg-white px-5 py-2.5 text-sm font-bold text-red-700 transition hover:bg-red-50">
                                Delete
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('admin.website-forms.index', $formType->routeKey()) }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-5 py-2.5 text-sm font-bold text-teal-800 transition hover:bg-teal-50">
                        Back To List
                    </a>
                </div>
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Contact Details</h2>
                <dl class="mt-5 space-y-4 text-sm">
                    <div>
                        <dt class="font-semibold text-slate-500">Name</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $submission->name ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Email</dt>
                        <dd class="mt-1 text-slate-900">
                            @if ($submission->email)
                                <a class="font-semibold text-teal-700 hover:text-teal-800" href="mailto:{{ $submission->email }}">{{ $submission->email }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Phone</dt>
                        <dd class="mt-1 text-slate-900">{{ $submission->phone ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Referrer</dt>
                        <dd class="mt-1 text-slate-900">{{ $submission->referrer_name ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Consent</dt>
                        <dd class="mt-1 text-slate-900">{{ $submission->consent_given ? 'Yes' : 'No' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Request Details</h2>
                <dl class="mt-5 space-y-4 text-sm">
                    <div>
                        <dt class="font-semibold text-slate-500">Interested In</dt>
                        <dd class="mt-1 text-slate-900">{{ $submission->interested_in ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Preferred Day / Time</dt>
                        <dd class="mt-1 text-slate-900">{{ $submission->preferred_time ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Source</dt>
                        <dd class="mt-1 text-slate-900">{{ $submission->source ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Tracking Source</dt>
                        <dd class="mt-1 text-slate-900">{{ $submission->tracking_source ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Page URL</dt>
                        <dd class="mt-1 break-all text-slate-900">
                            @if ($submission->page_url)
                                <a class="font-semibold text-teal-700 hover:text-teal-800" href="{{ $submission->page_url }}" rel="noreferrer" target="_blank">{{ $submission->page_url }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-black text-slate-950">Message</h2>
            <p class="mt-4 whitespace-pre-line text-sm leading-7 text-slate-700">{{ $submission->message ?: 'No message provided.' }}</p>
        </section>

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-black text-slate-950">Admin Notes</h2>
            <p class="mt-4 whitespace-pre-line text-sm leading-7 text-slate-700">{{ $submission->admin_notes ?: 'No admin notes yet.' }}</p>
            @if ($submission->prospect)
                <p class="mt-4 text-sm text-slate-500">
                    Linked CRM prospect:
                    <a class="font-semibold text-teal-700 hover:text-teal-800" href="{{ route('admin.crm.prospects.show', $submission->prospect) }}">
                        #{{ $submission->prospect_id }} ({{ $submission->prospect->first_name }} {{ $submission->prospect->last_name }})
                    </a>
                </p>
            @endif
        </section>
    </div>
@endsection
