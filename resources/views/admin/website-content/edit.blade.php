@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        @if (session('status'))
            <div class="rounded-md border border-teal-100 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
                {{ session('status') }}
            </div>
        @endif

        <section class="overflow-hidden rounded-lg border border-teal-100 bg-gradient-to-br from-[#041f1e] via-[#062926] to-[#031a19] text-white shadow-lg">
            <div class="relative px-6 py-7 sm:px-8">
                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-3xl">
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-100">Website Content</p>
                        <h1 class="mt-3 text-3xl font-black tracking-normal sm:text-4xl">Manage public site details</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-teal-50/75">
                            Update contact info, social links, and other website values that used to be hardcoded in the frontend.
                        </p>
                    </div>
                    <a
                        class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-3 text-sm font-bold text-[#031a19] transition hover:bg-teal-300"
                        href="{{ $frontendUrl }}"
                        rel="noreferrer"
                        target="_blank"
                    >
                        Open Website
                    </a>
                </div>
            </div>
        </section>

        <form method="POST" action="{{ route('admin.website-content.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Company & contact</h2>
                <p class="mt-1 text-sm text-slate-500">Shown in the footer, About contact card, and click-to-call/email links.</p>

                <div class="mt-5 grid gap-5">
                    @foreach (['site.company_name', 'site.support_email', 'site.support_phone', 'site.location'] as $key)
                        @php
                            $field = $fields[$key];
                            $input = str_replace('.', '_', $key);
                        @endphp
                        <div>
                            <label for="{{ $input }}" class="block text-sm font-semibold text-slate-700">{{ $field['label'] }}</label>
                            <input
                                id="{{ $input }}"
                                name="{{ $input }}"
                                type="{{ $field['type'] === 'email' ? 'email' : 'text' }}"
                                @if ($key === 'site.company_name') required @endif
                                maxlength="255"
                                value="{{ old($input, $values[$key] ?? $field['default']) }}"
                                class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                            >
                            <p class="mt-2 text-xs font-medium text-slate-500">{{ $field['help'] }}</p>
                            @error($input)
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Social & guides</h2>
                <p class="mt-1 text-sm text-slate-500">Footer Hydrogen Success Guides and related outbound links.</p>

                <div class="mt-5 grid gap-5">
                    @foreach (['site.facebook_url', 'site.youtube_url', 'site.consumers_guide_url'] as $key)
                        @php
                            $field = $fields[$key];
                            $input = str_replace('.', '_', $key);
                        @endphp
                        <div>
                            <label for="{{ $input }}" class="block text-sm font-semibold text-slate-700">{{ $field['label'] }}</label>
                            <input
                                id="{{ $input }}"
                                name="{{ $input }}"
                                type="url"
                                maxlength="500"
                                value="{{ old($input, $values[$key] ?? $field['default']) }}"
                                class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                            >
                            <p class="mt-2 text-xs font-medium text-slate-500">{{ $field['help'] }}</p>
                            @error($input)
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-2.5 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
                    Save Website Content
                </button>
                <a href="{{ route('admin.settings') }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-5 py-2.5 text-sm font-bold text-teal-800 shadow-sm transition hover:bg-teal-50">
                    Back To Settings
                </a>
            </div>
        </form>
    </div>
@endsection
