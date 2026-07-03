@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-5xl">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-600">CRM Module</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">{{ $title ?? 'Coming Soon' }}</h1>
            <p class="mt-3 max-w-2xl text-slate-600">{{ $description ?? 'This module is planned in the CRM rollout phases. See docs/CRM_FUNNEL_SYSTEM.md for the implementation roadmap.' }}</p>

            <div class="mt-6 rounded-xl border border-teal-100 bg-teal-50 p-4 text-sm text-teal-900">
                <strong>Phase:</strong> {{ $phase ?? 'Upcoming' }}
            </div>

            <a class="mt-6 inline-flex text-sm font-semibold text-teal-700 hover:text-teal-900" href="{{ route('admin.dashboard') }}">
                ← Back to Dashboard
            </a>
        </div>
    </div>
@endsection
