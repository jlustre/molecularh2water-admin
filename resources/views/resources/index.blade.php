@php
    $categoryMeta = [
        'documents' => 'Guides and PDFs',
        'videos' => 'Videos and replays',
        'links' => 'Research and references',
        'images' => 'Visual resources',
        'downloads' => 'Downloadable files',
        'embedded' => 'Embedded resources',
    ];

    $categoryIcons = [
        'documents' => 'DOC',
        'videos' => 'VID',
        'links' => 'URL',
        'images' => 'IMG',
        'downloads' => 'DL',
        'embedded' => 'EMB',
    ];
@endphp

@extends('layouts.portal')

@section('content')
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <section class="overflow-hidden rounded-lg border border-teal-200/[0.18] bg-white/[0.07] text-white shadow-lg backdrop-blur-xl">
                <div class="relative px-6 py-7 sm:px-8">
                    <div class="absolute inset-0 opacity-[0.08] [background-image:linear-gradient(rgba(45,212,191,.85)_1px,transparent_1px),linear-gradient(90deg,rgba(45,212,191,.85)_1px,transparent_1px)] [background-size:36px_36px]"></div>
                    <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div class="max-w-3xl">
                            <p class="inline-flex items-center gap-2 rounded-full border border-teal-300/25 bg-white/[0.07] px-3 py-1 text-xs font-bold uppercase tracking-[0.22em] text-teal-100">
                                <span class="size-2 rounded-full bg-teal-300 shadow-[0_0_14px_rgba(45,212,191,0.9)]"></span>
                                Resource Center
                            </p>
                            <h2 class="mt-5 text-3xl font-black tracking-normal sm:text-4xl">Browse your published media resources.</h2>
                            <p class="mt-3 max-w-2xl text-sm leading-7 text-teal-50/[0.72]">
                                Documents, videos, links, downloads, and supporting media shared by the admin team are available here in read-only mode.
                            </p>
                        </div>
                        <form method="GET" action="{{ route('resources') }}" class="flex flex-col gap-3 sm:flex-row">
                            <select name="category" class="rounded-md border border-teal-100 bg-white px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400">
                                <option value="">All resources</option>
                                @foreach ($categories as $value => $label)
                                    <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-3 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
                                Filter
                            </button>
                        </form>
                    </div>
                </div>
            </section>

            <section class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($categories as $value => $label)
                    @php $count = (int) ($categoryCounts[$value] ?? 0); @endphp
                    <a href="{{ route('resources', ['category' => $value]) }}" class="rounded-lg border border-white/10 bg-white/[0.94] p-5 text-slate-900 shadow-lg shadow-teal-950/10 transition hover:-translate-y-0.5 hover:shadow-xl">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-500">{{ $categoryMeta[$value] ?? 'Published media' }}</p>
                                <h3 class="mt-2 text-xl font-black text-slate-950">{{ $label }}</h3>
                            </div>
                            <span class="flex size-11 items-center justify-center rounded-md bg-teal-50 text-xs font-black text-teal-700">{{ $categoryIcons[$value] ?? 'H2' }}</span>
                        </div>
                        <p class="mt-5 text-sm font-semibold text-teal-700">{{ $count }} available</p>
                    </a>
                @endforeach
            </section>

            <section class="rounded-lg border border-white/10 bg-white/[0.94] p-6 text-slate-900 shadow-lg shadow-teal-950/10">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Published Library</p>
                        <h2 class="mt-2 text-2xl font-black tracking-normal text-slate-950">Resources</h2>
                    </div>
                    <form method="GET" action="{{ route('resources') }}" class="flex flex-col gap-2 sm:flex-row">
                        <input name="search" type="search" value="{{ request('search') }}" class="rounded-full border border-teal-100 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-teal-400 focus:ring-teal-400 sm:w-72" placeholder="Search resources...">
                        <button type="submit" class="rounded-full bg-teal-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-teal-700">Search</button>
                    </form>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @forelse ($resources as $resource)
                        @php
                            $fileUrl = $resource->file_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($resource->file_path) : null;
                            $resourceUrl = $fileUrl ?: $resource->url;
                            $shareUrl = route('media.show', $resource);
                        @endphp
                        <article class="flex min-h-64 flex-col rounded-lg border border-teal-100 bg-white p-5 shadow-sm">
                            <div class="flex items-start justify-between gap-4">
                                <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.12em] text-teal-700">
                                    {{ $categories[$resource->category] ?? ucfirst($resource->category) }}
                                </span>
                                @if ($resource->isPdf())
                                    <span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-bold text-cyan-700">PDF</span>
                                @endif
                            </div>
                            <h3 class="mt-4 text-lg font-black leading-snug text-slate-950">{{ $resource->title }}</h3>
                            <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-500">
                                {{ $resource->description ?: 'Published resource from the Molecular H2 Water media library.' }}
                            </p>
                            @if ($resource->file_name)
                                <p class="mt-3 truncate text-xs font-semibold text-slate-400">{{ $resource->file_name }}</p>
                            @endif
                            <div class="mt-auto grid gap-2 pt-5">
                                @if ($resourceUrl)
                                    <a href="{{ $resourceUrl }}" target="_blank" rel="noreferrer" class="inline-flex w-full items-center justify-center rounded-md bg-teal-400 px-4 py-2.5 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.2)] transition hover:bg-teal-300">
                                        {{ $resource->file_path ? 'Open File' : 'Open Link' }}
                                    </a>
                                @else
                                    <span class="inline-flex w-full items-center justify-center rounded-md bg-slate-100 px-4 py-2.5 text-sm font-bold text-slate-400">No link available</span>
                                @endif
                                <a href="{{ $shareUrl }}" target="_blank" rel="noreferrer" class="inline-flex w-full items-center justify-center gap-2 rounded-md border border-teal-100 bg-white px-4 py-2.5 text-sm font-bold text-teal-800 shadow-sm transition hover:bg-teal-50">
                                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 13a5 5 0 0 0 7.07 0l2.12-2.12a5 5 0 0 0-7.07-7.07L11 4.93"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 11a5 5 0 0 0-7.07 0L4.8 13.12a5 5 0 0 0 7.07 7.07L13 19.07"/>
                                    </svg>
                                    Share Link
                                </a>
                            </div>
                        </article>
                    @empty
                        <div class="md:col-span-2 xl:col-span-3 rounded-lg border border-dashed border-teal-200 bg-teal-50 p-10 text-center">
                            <p class="text-lg font-black text-slate-950">No resources available yet</p>
                            <p class="mt-2 text-sm text-slate-500">Published admin media will appear here once resources are added.</p>
                        </div>
                    @endforelse
                </div>

                <div class="mt-6">
                    {{ $resources->links() }}
                </div>
            </section>
        </div>
    </div>
@endsection
