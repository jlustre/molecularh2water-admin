@php
    $user = auth()->user();
    $userName = $user?->name ?? 'Member';
    $userEmail = $user?->email ?? '';
    $initials = collect(explode(' ', trim($userName)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->join('');
    $initials = $initials !== '' ? mb_strtoupper($initials) : 'AU';
    $avatarUrl = $user?->avatar_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar_path) : null;

    $sidebarLinks = [
        ['label' => 'Dashboard', 'href' => route('dashboard'), 'active' => false, 'icon' => 'dashboard'],
        ['label' => 'Resources', 'href' => route('resources'), 'active' => true, 'icon' => 'resources'],
        ['label' => 'Admin Portal', 'href' => route('admin.dashboard'), 'active' => false, 'icon' => 'admin'],
        ['label' => 'Profile', 'href' => route('profile'), 'active' => false, 'icon' => 'profile'],
        ['label' => 'Settings', 'href' => route('admin.settings'), 'active' => false, 'icon' => 'settings'],
    ];

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

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Resources | {{ config('app.name', 'Molecular H2 Water Admin') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#041f1e] font-sans text-white antialiased selection:bg-teal-300 selection:text-[#031a19]">
        <div class="relative min-h-screen overflow-hidden bg-[linear-gradient(135deg,#041f1e_0%,#062926_48%,#031a19_100%)]">
            <div class="absolute inset-0 opacity-[0.08] [background-image:linear-gradient(rgba(45,212,191,.85)_1px,transparent_1px),linear-gradient(90deg,rgba(45,212,191,.85)_1px,transparent_1px)] [background-size:42px_42px]"></div>
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-teal-300/70 to-transparent"></div>

            <div class="relative flex min-h-screen">
                <aside class="hidden w-72 shrink-0 border-r border-teal-200/[0.14] bg-[#041f1e]/90 p-5 shadow-[18px_0_50px_rgba(0,0,0,0.18)] backdrop-blur-xl lg:flex lg:flex-col">
                    <a href="{{ url('/') }}" class="mb-8 flex items-center gap-3 rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-300">
                        <span class="flex size-12 items-center justify-center rounded-full border border-teal-300/[0.45] bg-teal-300/10 shadow-[0_0_24px_rgba(45,212,191,0.18)]">
                            <span class="font-mono text-lg font-bold tracking-tight text-teal-200">H2</span>
                        </span>
                        <span>
                            <span class="block text-sm font-semibold tracking-wide text-white">Molecular H2 Water</span>
                            <span class="block text-xs font-semibold uppercase tracking-[0.22em] text-teal-200/70">Client Portal</span>
                        </span>
                    </a>

                    <nav class="flex-1 space-y-7" aria-label="Resources navigation">
                        <div>
                            <p class="px-3 text-xs font-bold uppercase tracking-[0.22em] text-teal-200/45">Workspace</p>
                            <div class="mt-3 space-y-1.5">
                                @foreach ($sidebarLinks as $link)
                                    <a href="{{ $link['href'] }}" class="flex items-center gap-3 rounded-md border px-3 py-2.5 text-sm font-semibold transition {{ $link['active'] ? 'border-teal-300/35 bg-teal-300/15 text-white shadow-[0_0_18px_rgba(45,212,191,0.12)]' : 'border-transparent text-teal-50/70 hover:bg-white/[0.07] hover:text-white' }}">
                                        <span class="flex size-9 items-center justify-center rounded-md bg-white/[0.06] text-teal-200">
                                            @if ($link['icon'] === 'dashboard')
                                                <svg width="18" height="18" fill="none" viewBox="0 0 18 18"><rect x="2" y="2" width="5.5" height="5.5" rx="1.2" fill="currentColor"/><rect x="10.5" y="2" width="5.5" height="5.5" rx="1.2" fill="currentColor" opacity=".6"/><rect x="2" y="10.5" width="5.5" height="5.5" rx="1.2" fill="currentColor" opacity=".6"/><rect x="10.5" y="10.5" width="5.5" height="5.5" rx="1.2" fill="currentColor" opacity=".35"/></svg>
                                            @elseif ($link['icon'] === 'resources')
                                                <svg width="18" height="18" fill="none" viewBox="0 0 18 18"><path d="M4 3.5h7.5L14 6v8.5H4v-11Z" stroke="currentColor" stroke-width="1.5"/><path d="M7 8h4M7 11h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                            @elseif ($link['icon'] === 'admin')
                                                <svg width="18" height="18" fill="none" viewBox="0 0 18 18"><path d="M3 5.5 9 2l6 3.5v7L9 16l-6-3.5v-7Z" stroke="currentColor" stroke-width="1.5"/><path d="M6.5 9h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                            @elseif ($link['icon'] === 'profile')
                                                <svg width="18" height="18" fill="none" viewBox="0 0 18 18"><circle cx="9" cy="6" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M3.5 15c0-2.5 2.5-4.5 5.5-4.5s5.5 2 5.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                            @else
                                                <svg width="18" height="18" fill="none" viewBox="0 0 18 18"><circle cx="9" cy="9" r="2.3" stroke="currentColor" stroke-width="1.5"/><path d="M9 2v2M9 14v2M2 9h2M14 9h2M4.1 4.1l1.4 1.4M12.5 12.5l1.4 1.4M4.1 13.9l1.4-1.4M12.5 5.5l1.4-1.4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                            @endif
                                        </span>
                                        {{ $link['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        <div class="rounded-lg border border-teal-200/[0.14] bg-white/[0.05] p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-200/55">Read-only</p>
                            <p class="mt-3 text-sm leading-6 text-teal-50/70">Resources are managed by admins and shown here when published.</p>
                        </div>
                    </nav>

                    <div class="rounded-lg border border-teal-200/[0.14] bg-white/[0.05] p-4">
                        <p class="text-sm font-semibold text-white">{{ $userName }}</p>
                        <p class="mt-1 truncate text-xs text-teal-100/55">{{ $userEmail }}</p>
                    </div>
                </aside>

                <main class="flex min-w-0 flex-1 flex-col">
                    <header class="sticky top-0 z-40 border-b border-teal-100/20 bg-white/[0.88] text-slate-900 shadow-lg shadow-teal-950/5 backdrop-blur-xl">
                        <div class="flex h-20 items-center gap-4 px-4 sm:px-8 lg:px-10">
                            <div class="flex min-w-0 flex-1 items-center gap-4">
                                <a href="{{ url('/') }}" class="flex items-center gap-3 rounded-md lg:hidden">
                                    <span class="flex size-10 items-center justify-center rounded-full border border-teal-600/30 bg-teal-50 text-sm font-bold text-teal-800">H2</span>
                                </a>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Resources</p>
                                    <h1 class="truncate text-xl font-black tracking-normal text-slate-950">Published media library</h1>
                                </div>
                            </div>

                            <div class="hidden flex-1 justify-center px-4 xl:flex">
                                <form method="GET" action="{{ route('resources') }}" class="relative w-full max-w-xl" role="search" aria-label="Resource search">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-teal-500">
                                        <svg width="20" height="20" fill="none" viewBox="0 0 20 20"><circle cx="9" cy="9" r="7" stroke="currentColor" stroke-width="2"/><path d="M16 16l-3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                    </span>
                                    <input name="search" value="{{ request('search') }}" type="search" class="w-full rounded-full border border-teal-100 bg-white/80 py-3 pl-12 pr-5 text-sm font-medium text-slate-900 shadow-inner placeholder:text-teal-900/40 focus:border-teal-400 focus:ring-2 focus:ring-teal-300/40" placeholder="Search resources...">
                                </form>
                            </div>

                            <div class="ml-auto flex items-center gap-2 sm:gap-3">
                                <a href="{{ route('dashboard') }}" class="hidden rounded-full bg-teal-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-teal-700 sm:inline-flex">
                                    Dashboard
                                </a>

                                <details class="group relative">
                                    <summary aria-label="Open user menu" class="flex cursor-pointer list-none items-center gap-2 rounded-full py-1 pl-1 pr-2 transition hover:bg-teal-50 focus:outline-none focus:ring-2 focus:ring-teal-400 [&::-webkit-details-marker]:hidden">
                                        <span class="relative flex items-center">
                                            @if ($avatarUrl)
                                                <img src="{{ $avatarUrl }}" alt="{{ $userName }} avatar" class="size-10 rounded-full border-2 border-white object-cover shadow-inner">
                                            @else
                                                <span class="inline-flex size-10 items-center justify-center rounded-full border-2 border-white bg-gradient-to-br from-teal-400/80 to-teal-700/80 text-sm font-bold text-white shadow-inner">{{ $initials }}</span>
                                            @endif
                                            <span class="absolute bottom-0 right-0 size-3 rounded-full border-2 border-white bg-emerald-400"></span>
                                        </span>
                                        <span class="hidden flex-col items-start sm:flex">
                                            <span class="max-w-32 truncate text-sm font-semibold leading-tight text-slate-900">{{ $userName }}</span>
                                            <span class="text-xs text-teal-700">Member</span>
                                        </span>
                                        <svg width="18" height="18" fill="none" viewBox="0 0 18 18" class="text-teal-700 transition group-open:rotate-180"><path d="M6 7l3 3 3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </summary>

                                    <div class="absolute right-0 top-full z-50 mt-3 w-64 overflow-hidden rounded-lg border border-teal-100 bg-white shadow-xl shadow-teal-950/10">
                                        <div class="border-b border-teal-50 px-4 py-3">
                                            <p class="truncate text-sm font-semibold text-slate-900">{{ $userName }}</p>
                                            <p class="truncate text-xs text-teal-700">{{ $userEmail }}</p>
                                        </div>

                                        <div class="py-2">
                                            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-teal-50 hover:text-teal-800">Dashboard</a>
                                            <a href="{{ route('profile') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-teal-50 hover:text-teal-800">Profile</a>
                                        </div>

                                        <form method="POST" action="{{ route('logout') }}" class="border-t border-teal-50">
                                            @csrf
                                            <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm font-semibold text-red-600 transition hover:bg-red-50">
                                                Log off
                                            </button>
                                        </form>
                                    </div>
                                </details>
                            </div>
                        </div>
                    </header>

                    <section class="flex-1 overflow-y-auto px-4 py-6 sm:px-8 lg:px-10">
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
                    </section>
                </main>
            </div>
        </div>
    </body>
</html>
