@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Installation Completion</p>
                    <h1 class="mt-2 text-3xl font-black text-slate-950">{{ $questionnaire->full_name }}</h1>
                    <p class="mt-2 text-sm text-slate-500">Completed {{ $installation->completed_at?->format('M j, Y g:i A') }}</p>
                </div>
                <a class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-4 py-2.5 text-sm font-bold text-teal-800 hover:bg-teal-50" href="{{ route('admin.installation-questionnaires.show', $questionnaire) }}">Back to questionnaire</a>
            </div>
        </section>

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-black text-slate-950">Completion details</h2>
            <dl class="mt-5 grid gap-5 text-sm sm:grid-cols-2">
                <div><dt class="font-semibold text-slate-500">Installer</dt><dd class="mt-1 text-slate-900">{{ $installation->completion_installer_name }}</dd></div>
                <div><dt class="font-semibold text-slate-500">Installation date</dt><dd class="mt-1 text-slate-900">{{ $installation->installation_date?->format('F j, Y') }}</dd></div>
                <div class="sm:col-span-2"><dt class="font-semibold text-slate-500">Customer address</dt><dd class="mt-1 whitespace-pre-line text-slate-900">{{ $installation->completion_address }}</dd></div>
                <div class="sm:col-span-2"><dt class="font-semibold text-slate-500">Product installed</dt><dd class="mt-1 text-slate-900">{{ $installation->installed_product }}</dd></div>
                <div class="sm:col-span-2"><dt class="font-semibold text-slate-500">Installation details</dt><dd class="mt-1 whitespace-pre-line text-slate-900">{{ $installation->completion_details ?: 'None provided' }}</dd></div>
                <div class="sm:col-span-2"><dt class="font-semibold text-slate-500">Installer notes</dt><dd class="mt-1 whitespace-pre-line text-slate-900">{{ $installation->installer_completion_notes ?: 'None provided' }}</dd></div>
                <div><dt class="font-semibold text-slate-500">Customer signature name</dt><dd class="mt-1 text-slate-900">{{ $installation->customer_signature_name }}</dd></div>
                <div><dt class="font-semibold text-slate-500">Signed at</dt><dd class="mt-1 text-slate-900">{{ $installation->customer_signed_at?->format('M j, Y g:i A') }}</dd></div>
            </dl>
        </section>

        @if ($installation->customer_signature_path)
            <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Customer signature</h2>
                <img class="mt-4 max-w-md rounded-md border border-slate-200 bg-white p-3" src="{{ route('admin.installation-questionnaires.completion.signature', $questionnaire) }}" alt="Customer signature">
            </section>
        @endif
    </div>
@endsection