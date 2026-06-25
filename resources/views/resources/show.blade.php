@php
    $categoryLabel = $categories[$resource->category] ?? ucfirst($resource->category);
    $fileUrl = $resource->file_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($resource->file_path) : null;
    $resourceUrl = $fileUrl ?: $resource->url;
    $shareUrl = route('media.show', $resource);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $resource->title }} | {{ config('app.name', 'Molecular H2 Water Admin') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#041f1e] font-sans text-white antialiased selection:bg-teal-300 selection:text-[#031a19]">
        <main class="relative min-h-screen overflow-hidden bg-[linear-gradient(135deg,#041f1e_0%,#062926_48%,#031a19_100%)]">
            <div class="absolute inset-0 opacity-[0.08] [background-image:linear-gradient(rgba(45,212,191,.85)_1px,transparent_1px),linear-gradient(90deg,rgba(45,212,191,.85)_1px,transparent_1px)] [background-size:42px_42px]"></div>
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-teal-300/70 to-transparent"></div>

            <div class="relative mx-auto flex min-h-screen max-w-5xl flex-col px-4 py-6 sm:px-6 lg:px-8">
                <header class="flex items-center justify-between gap-4">
                    <a href="{{ url('/') }}" class="flex items-center gap-3 rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-300">
                        <span class="flex size-11 items-center justify-center rounded-full border border-teal-300/[0.45] bg-teal-300/10 shadow-[0_0_24px_rgba(45,212,191,0.18)]">
                            <span class="font-mono text-base font-bold tracking-tight text-teal-200">H2</span>
                        </span>
                        <span>
                            <span class="block text-sm font-semibold tracking-wide text-white">Molecular H2 Water</span>
                            <span class="block text-xs font-semibold uppercase tracking-[0.22em] text-teal-200/70">Shared Resource</span>
                        </span>
                    </a>

                    @auth
                        <a href="{{ route('resources') }}" class="inline-flex items-center justify-center rounded-md border border-teal-200/25 bg-white/[0.08] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-white/[0.12]">
                            Resources
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-md border border-teal-200/25 bg-white/[0.08] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-white/[0.12]">
                            Login
                        </a>
                    @endauth
                </header>

                <section class="flex flex-1 items-center py-10">
                    <article class="w-full overflow-hidden rounded-lg border border-teal-200/[0.18] bg-white/[0.94] text-slate-900 shadow-2xl shadow-teal-950/25">
                        <div class="grid gap-0 lg:grid-cols-[0.9fr_1.1fr]">
                            <aside class="bg-[#041f1e] p-6 text-white sm:p-8">
                                <p class="inline-flex items-center gap-2 rounded-full border border-teal-300/25 bg-white/[0.07] px-3 py-1 text-xs font-bold uppercase tracking-[0.22em] text-teal-100">
                                    <span class="size-2 rounded-full bg-teal-300 shadow-[0_0_14px_rgba(45,212,191,0.9)]"></span>
                                    {{ $categoryLabel }}
                                </p>

                                <div class="mt-10 flex aspect-square max-w-64 items-center justify-center rounded-lg border border-teal-200/20 bg-white/[0.06] text-teal-200">
                                    @if ($resource->isPdf())
                                        <svg class="size-20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 3v6h5M8 16h8M8 12h3"/>
                                        </svg>
                                    @elseif ($resource->isVideo())
                                        <svg class="size-20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                            <rect x="3" y="5" width="18" height="14" rx="3"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m10 9 5 3-5 3V9Z"/>
                                        </svg>
                                    @else
                                        <svg class="size-20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 13a5 5 0 0 0 7.07 0l2.12-2.12a5 5 0 0 0-7.07-7.07L11 4.93"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 11a5 5 0 0 0-7.07 0L4.8 13.12a5 5 0 0 0 7.07 7.07L13 19.07"/>
                                        </svg>
                                    @endif
                                </div>

                                <p class="mt-8 text-sm leading-6 text-teal-50/65">
                                    This public link can be opened without creating an account.
                                </p>
                            </aside>

                            <div class="p-6 sm:p-8 lg:p-10">
                                <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Shared Media</p>
                                <h1 class="mt-3 text-3xl font-black tracking-normal text-slate-950 sm:text-4xl">{{ $resource->title }}</h1>

                                <p class="mt-5 text-base leading-7 text-slate-600">
                                    {{ $resource->description ?: 'A shared resource from the Molecular H2 Water media library.' }}
                                </p>

                                @if ($resource->file_name)
                                    <div class="mt-6 rounded-md border border-teal-100 bg-teal-50 px-4 py-3">
                                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-700">File</p>
                                        <p class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $resource->file_name }}</p>
                                    </div>
                                @endif

                                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                                    @if ($resourceUrl)
                                        <a href="{{ $resourceUrl }}" target="_blank" rel="noreferrer" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-3 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
                                            {{ $resource->file_path ? 'Open File' : 'Open Link' }}
                                        </a>
                                    @endif
                                    <a href="{{ $shareUrl }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-5 py-3 text-sm font-bold text-teal-800 shadow-sm transition hover:bg-teal-50">
                                        Public Share Link
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                </section>
            </div>
        </main>
    </body>
</html>
