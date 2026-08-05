@extends('layouts.admin')

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
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Installation Questionnaire</p>
                    <h1 class="mt-2 text-3xl font-black tracking-normal text-slate-950">{{ $questionnaire->full_name }}</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Submitted {{ $questionnaire->created_at?->format('M j, Y g:i A') }}.</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.installation-questionnaires.edit', $questionnaire) }}" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-2.5 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
                        Edit Submission
                    </a>
                    <a href="{{ route('admin.installation-questionnaires.index') }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-5 py-2.5 text-sm font-bold text-teal-800 transition hover:bg-teal-50">
                        Back To List
                    </a>
                </div>
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Customer Details</h2>
                <dl class="mt-5 space-y-4 text-sm">
                    <div>
                        <dt class="font-semibold text-slate-500">Name</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $questionnaire->full_name }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Email</dt>
                        <dd class="mt-1 text-slate-900">
                            <a class="font-semibold text-teal-700 hover:text-teal-800" href="mailto:{{ $questionnaire->email }}">{{ $questionnaire->email }}</a>
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Phone</dt>
                        <dd class="mt-1 text-slate-900">{{ $questionnaire->phone }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Own or Rent</dt>
                        <dd class="mt-1 text-slate-900">{{ $questionnaire->ownershipLabel() }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Installation Address</h2>
                <p class="mt-5 whitespace-pre-line text-sm leading-7 text-slate-700">{{ $questionnaire->formatted_address }}</p>
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Property & Water</h2>
                <dl class="mt-5 space-y-4 text-sm">
                    <div>
                        <dt class="font-semibold text-slate-500">Property Type</dt>
                        <dd class="mt-1 text-slate-900">{{ $questionnaire->property_type }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Existing Equipment</dt>
                        <dd class="mt-1 text-slate-900">
                            @if ($questionnaire->existing_equipment)
                                <ul class="list-disc space-y-1 pl-5">
                                    @foreach ($questionnaire->existing_equipment as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ul>
                            @else
                                None selected
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Water Source</dt>
                        <dd class="mt-1 text-slate-900">
                            {{ $questionnaire->water_source }}
                            @if ($questionnaire->water_source === 'Other' && $questionnaire->water_source_other)
                                — {{ $questionnaire->water_source_other }}
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Sink Photos</h2>
                @php $sinkPhotos = $questionnaire->sinkPhotoItems(); @endphp
                @if ($sinkPhotos !== [])
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        @foreach ($sinkPhotos as $index => $photo)
                            @php
                                $photoUrl = route('admin.installation-questionnaires.photos.show', [
                                    'installation_questionnaire' => $questionnaire,
                                    'photo' => $index,
                                ]);
                            @endphp
                            <div class="space-y-3">
                                <img
                                    alt="Uploaded sink photo {{ $index + 1 }}"
                                    class="max-h-64 w-full rounded-lg border border-teal-100 object-cover"
                                    src="{{ $photoUrl }}"
                                >
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="truncate text-xs font-semibold text-slate-500">
                                        {{ $photo['original_name'] ?: 'Photo '.($index + 1) }}
                                    </p>
                                    <a
                                        class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-3 py-1.5 text-xs font-bold text-teal-800 transition hover:bg-teal-50"
                                        href="{{ $photoUrl }}"
                                        rel="noreferrer"
                                        target="_blank"
                                    >
                                        Open
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-5 text-sm text-slate-500">No sink photos were uploaded.</p>
                @endif
            </div>
        </section>

        @if ($questionnaire->special_requirements)
            <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Special Requirements</h2>
                <p class="mt-3 whitespace-pre-wrap text-sm leading-7 text-slate-700">{{ $questionnaire->special_requirements }}</p>
            </section>
        @endif

        @if ($questionnaire->additional_notes)
            <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Additional Notes</h2>
                <p class="mt-3 whitespace-pre-wrap text-sm leading-7 text-slate-700">{{ $questionnaire->additional_notes }}</p>
            </section>
        @endif

        <section class="rounded-lg border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">
            Public questionnaire page: <a href="{{ $installationUrl }}" target="_blank" rel="noreferrer" class="font-semibold text-teal-700 hover:text-teal-800">{{ $installationUrl }}</a>
        </section>
    </div>
@endsection
