@extends('layouts.admin')

@php
    $categoryMeta = [
        'documents' => 'PDF, DOCX, brochures',
        'videos' => 'Training, testimonials',
        'links' => 'Studies, references, URLs',
        'images' => 'Products, banners, icons',
        'downloads' => 'Downloadable files',
        'embedded' => 'Embeds and resources',
    ];

    $statusClasses = [
        'published' => 'bg-emerald-50 text-emerald-700',
        'review' => 'bg-amber-50 text-amber-700',
        'draft' => 'bg-slate-100 text-slate-600',
        'archived' => 'bg-zinc-100 text-zinc-600',
    ];

    $storageMb = $storageBytes > 0 ? round($storageBytes / 1024 / 1024, 1) : 0;
    $storagePercent = min(100, max(8, (int) round(($storageBytes / (250 * 1024 * 1024)) * 100)));
@endphp

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
                            Media Library
                        </p>
                        <h1 class="mt-5 text-3xl font-black tracking-normal sm:text-4xl">Organize every asset by type, source, and purpose.</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-teal-50/[0.72]">
                            Create, edit, publish, archive, and delete documents, videos, external links, images, downloads, and educational resources.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('admin.media.create', ['category' => 'documents']) }}" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-3 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
                            Add Media
                        </a>
                        <a href="{{ route('admin.media.create', ['category' => 'links', 'mode' => 'media-link']) }}" class="inline-flex items-center justify-center rounded-md border border-teal-200/30 bg-white/[0.08] px-5 py-3 text-sm font-bold text-white transition hover:bg-white/[0.12]">
                            Add Media Link
                        </a>
                        <a href="{{ route('admin.media.create', ['category' => 'videos', 'mode' => 'video-link']) }}" class="inline-flex items-center justify-center rounded-md border border-teal-200/30 bg-white/[0.08] px-5 py-3 text-sm font-bold text-white transition hover:bg-white/[0.12]">
                            Add Video Link
                        </a>
                        <form method="POST" action="{{ route('admin.media.update-seeder') }}">
                            @csrf
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-md border border-teal-200/30 bg-white/[0.08] px-5 py-3 text-sm font-bold text-white transition hover:bg-white/[0.12]">
                                Update Seeder
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($categories as $value => $label)
                @php $count = (int) ($categoryCounts[$value] ?? 0); @endphp
                <a href="{{ route('admin.media.index', ['category' => $value]) }}" class="rounded-lg border border-teal-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-500">{{ $categoryMeta[$value] ?? 'Media resources' }}</p>
                            <h2 class="mt-2 text-xl font-black text-slate-950">{{ $label }}</h2>
                        </div>
                        <span class="flex size-11 items-center justify-center rounded-md bg-teal-50 text-sm font-black text-teal-700">{{ $count }}</span>
                    </div>
                    <div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-teal-400" style="width: {{ min(92, 18 + ($count * 12)) }}%"></div>
                    </div>
                </a>
            @endforeach
        </section>

        <section>
            <div class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Manage Assets</p>
                        <h2 class="mt-2 text-2xl font-black tracking-normal text-slate-950">Media queue</h2>
                    </div>
                    <form method="GET" action="{{ route('admin.media.index') }}" class="flex flex-col gap-2 sm:flex-row">
                        <select name="category" class="rounded-full border border-teal-100 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400">
                            <option value="">All categories</option>
                            @foreach ($categories as $value => $label)
                                <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <input name="search" type="search" value="{{ request('search') }}" class="rounded-full border border-teal-100 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-teal-400 focus:ring-teal-400 sm:w-72" placeholder="Search media...">
                        <button type="submit" class="rounded-full bg-teal-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-teal-700">Filter</button>
                    </form>
                </div>

                <div class="mt-6 overflow-hidden rounded-lg border border-slate-100">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Updated</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                            @forelse ($mediaItems as $mediaItem)
                                @php
                                    $videoUrl = $mediaItem->url ?: ($mediaItem->file_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($mediaItem->file_path) : null);
                                @endphp
                                <tr class="transition hover:bg-teal-50/50">
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-slate-900">{{ $mediaItem->title }}</div>
                                        @if ($mediaItem->file_path)
                                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($mediaItem->file_path) }}" target="_blank" rel="noreferrer" class="mt-1 inline-block max-w-xs truncate text-xs font-medium text-teal-700 underline decoration-teal-300 underline-offset-4">
                                                {{ $mediaItem->file_name ?? basename($mediaItem->file_path) }}
                                            </a>
                                        @elseif ($mediaItem->url)
                                            <a href="{{ $mediaItem->url }}" target="_blank" rel="noreferrer" class="mt-1 inline-block max-w-xs truncate text-xs font-medium text-teal-700 underline decoration-teal-300 underline-offset-4">
                                                {{ $mediaItem->url }}
                                            </a>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">{{ $categories[$mediaItem->category] ?? ucfirst($mediaItem->category) }}</td>
                                    <td class="px-4 py-4">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClasses[$mediaItem->status] ?? 'bg-slate-100 text-slate-600' }}">
                                            {{ $statuses[$mediaItem->status] ?? ucfirst($mediaItem->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-slate-500">{{ $mediaItem->updated_at->diffForHumans() }}</td>
                                    <td class="px-4 py-4">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            @if ($mediaItem->isPdf())
                                                <a href="{{ route('admin.media.view-pdf', $mediaItem) }}" target="_blank" rel="noreferrer" aria-label="View PDF" title="View PDF" class="group relative inline-flex size-9 items-center justify-center rounded-md border border-cyan-100 bg-white text-cyan-700 transition hover:bg-cyan-50 hover:text-cyan-800 focus:outline-none focus:ring-2 focus:ring-cyan-300">
                                                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 3v6h5M8 16h8M8 12h3"/>
                                                    </svg>
                                                    <span class="sr-only">View PDF</span>
                                                    <span class="pointer-events-none absolute bottom-full right-0 z-10 mb-2 whitespace-nowrap rounded-md bg-slate-950 px-2.5 py-1.5 text-xs font-bold text-white opacity-0 shadow-lg transition group-hover:opacity-100 group-focus:opacity-100">View PDF</span>
                                                </a>
                                            @elseif ($mediaItem->isVideo() && $videoUrl)
                                                <a href="{{ $videoUrl }}" target="_blank" rel="noreferrer" aria-label="Open Video" title="Open Video" class="group relative inline-flex size-9 items-center justify-center rounded-md border border-violet-100 bg-white text-violet-700 transition hover:bg-violet-50 hover:text-violet-800 focus:outline-none focus:ring-2 focus:ring-violet-300">
                                                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                        <rect x="3" y="5" width="18" height="14" rx="3"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m10 9 5 3-5 3V9Z"/>
                                                    </svg>
                                                    <span class="sr-only">Open Video</span>
                                                    <span class="pointer-events-none absolute bottom-full right-0 z-10 mb-2 whitespace-nowrap rounded-md bg-slate-950 px-2.5 py-1.5 text-xs font-bold text-white opacity-0 shadow-lg transition group-hover:opacity-100 group-focus:opacity-100">Open Video</span>
                                                </a>
                                            @endif
                                            <a href="{{ route('media.show', $mediaItem) }}" target="_blank" rel="noreferrer" aria-label="Share" title="Share" class="group relative inline-flex size-9 items-center justify-center rounded-md border border-amber-100 bg-white text-amber-700 transition hover:bg-amber-50 hover:text-amber-800 focus:outline-none focus:ring-2 focus:ring-amber-200">
                                                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 13a5 5 0 0 0 7.07 0l2.12-2.12a5 5 0 0 0-7.07-7.07L11 4.93"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 11a5 5 0 0 0-7.07 0L4.8 13.12a5 5 0 0 0 7.07 7.07L13 19.07"/>
                                                </svg>
                                                <span class="sr-only">Share</span>
                                                <span class="pointer-events-none absolute bottom-full right-0 z-10 mb-2 whitespace-nowrap rounded-md bg-slate-950 px-2.5 py-1.5 text-xs font-bold text-white opacity-0 shadow-lg transition group-hover:opacity-100 group-focus:opacity-100">Share</span>
                                            </a>
                                            <a href="{{ route('admin.media.edit', $mediaItem) }}" aria-label="Edit" title="Edit" class="group relative inline-flex size-9 items-center justify-center rounded-md border border-teal-100 bg-white text-teal-700 transition hover:bg-teal-50 hover:text-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-300">
                                                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4 20 4.5-1 10-10a2.12 2.12 0 0 0-3-3l-10 10L4 20Z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14 7 3 3"/>
                                                </svg>
                                                <span class="sr-only">Edit</span>
                                                <span class="pointer-events-none absolute bottom-full right-0 z-10 mb-2 whitespace-nowrap rounded-md bg-slate-950 px-2.5 py-1.5 text-xs font-bold text-white opacity-0 shadow-lg transition group-hover:opacity-100 group-focus:opacity-100">Edit</span>
                                            </a>
                                            <form method="POST" action="{{ route('admin.media.destroy', $mediaItem) }}" onsubmit="return confirm('Delete this media item?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" aria-label="Delete" title="Delete" class="group relative inline-flex size-9 items-center justify-center rounded-md border border-red-100 bg-white text-red-600 transition hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-200">
                                                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 7h14M10 11v6M14 11v6M8 7l1-3h6l1 3M7 7l1 13h8l1-13"/>
                                                    </svg>
                                                    <span class="sr-only">Delete</span>
                                                    <span class="pointer-events-none absolute bottom-full right-0 z-10 mb-2 whitespace-nowrap rounded-md bg-slate-950 px-2.5 py-1.5 text-xs font-bold text-white opacity-0 shadow-lg transition group-hover:opacity-100 group-focus:opacity-100">Delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-12 text-center">
                                        <p class="text-base font-bold text-slate-900">No media items yet</p>
                                        <p class="mt-1 text-sm text-slate-500">Create your first document, video, link, image, download, or embedded resource.</p>
                                        <a href="{{ route('admin.media.create') }}" class="mt-4 inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-2.5 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
                                            Add Media
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-5">
                    {{ $mediaItems->links() }}
                </div>
            </div>
        </section>
    </div>
@endsection
