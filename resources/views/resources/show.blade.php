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
    <body class="min-h-screen bg-white font-sans text-slate-900 antialiased selection:bg-teal-200 selection:text-slate-900">
        <main class="relative min-h-screen overflow-hidden bg-[#f8fbfb]">
            <div class="absolute inset-0 opacity-40 [background-image:linear-gradient(rgba(13,148,136,.07)_1px,transparent_1px),linear-gradient(90deg,rgba(13,148,136,.07)_1px,transparent_1px)] [background-size:42px_42px]"></div>
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-teal-300/30 to-transparent"></div>

            <div class="relative mx-auto flex min-h-screen max-w-5xl flex-col px-4 py-6 sm:px-6 lg:px-8">
                <header class="flex items-center justify-between gap-4 rounded-lg border border-teal-100 bg-white px-5 py-4 shadow-sm">
                    <x-brand.mark :href="url('/')" portal-label="Shared Resource" size="md" :navigate="false" />

                    @auth
                        <a href="{{ route('resources') }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-800 transition hover:border-teal-300 hover:bg-teal-50">
                            Resources
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-800 transition hover:border-teal-300 hover:bg-teal-50">
                            Login
                        </a>
                    @endauth
                </header>

                <section class="flex flex-1 items-center py-10">
                    <article class="w-full overflow-hidden rounded-lg border border-teal-100 bg-white text-slate-900 shadow-[0_20px_50px_rgba(15,23,42,0.08)]">
                        <div class="grid gap-0 lg:grid-cols-[0.9fr_1.1fr]">
                            <aside class="border-b border-slate-100 bg-[#f8fbfb] p-6 text-slate-900 sm:border-b-0 sm:border-r sm:p-8">
                                <p class="inline-flex items-center gap-2 rounded-full border border-teal-200 bg-teal-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.22em] text-teal-800">
                                    <span class="size-2 rounded-full bg-teal-500 shadow-[0_0_10px_rgba(13,148,136,0.35)]"></span>
                                    {{ $categoryLabel }}
                                </p>

                                <div class="mt-10 flex aspect-square max-w-64 items-center justify-center rounded-lg border border-teal-100 bg-white text-teal-700">
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

                                <p class="mt-8 text-sm leading-6 text-slate-600">
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
