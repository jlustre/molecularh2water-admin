@props([
    'badge' => 'Secure Access',
    'heading' => 'One calm control point for your admin work.',
    'subtext' => 'Sign in to manage leads, appointments, content, customer messages, and system settings in the same teal operations environment.',
    'portalLabel' => null,
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Molecular H2 Water Admin') }}</title>
        @include('partials.favicon')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-white font-sans text-slate-900 antialiased selection:bg-teal-200 selection:text-slate-900">
        <main class="relative min-h-screen overflow-x-hidden bg-[#f8fbfb]">
            <div class="absolute inset-0 opacity-40 [background-image:linear-gradient(rgba(13,148,136,.07)_1px,transparent_1px),linear-gradient(90deg,rgba(13,148,136,.07)_1px,transparent_1px)] [background-size:42px_42px]"></div>
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-teal-300/30 to-transparent"></div>

            <section class="relative mx-auto flex min-h-screen w-full max-w-6xl items-center px-6 py-8 lg:px-10">
                <div class="grid w-full min-w-0 gap-8 lg:grid-cols-2 lg:items-stretch">
                    <aside class="hidden h-full flex-col overflow-hidden rounded-lg border-2 border-teal-400 bg-white shadow-[0_20px_50px_rgba(13,148,136,0.14)] lg:flex">
                        <div class="shrink-0 bg-white p-8 pb-5">
                            <x-brand.mark :href="url('/')" size="xl" align="center" />

                            <p class="mt-6 inline-flex items-center gap-2 rounded-full border-2 border-teal-200 bg-teal-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.22em] text-teal-800">
                                <span class="size-2 rounded-full bg-teal-500 shadow-[0_0_10px_rgba(13,148,136,0.35)]"></span>
                                {{ $badge }}
                            </p>
                            <h1 class="mt-4 text-3xl font-black leading-tight tracking-normal text-slate-900 xl:text-4xl">
                                {{ $heading }}
                            </h1>
                            <p class="mt-3 text-sm leading-6 text-slate-600">
                                {{ $subtext }}
                            </p>
                        </div>

                        <div class="border-t-2 border-teal-200 bg-white">
                            <img
                                src="{{ asset('images/auth/managing-h2-business.png') }}"
                                alt="Managing My H2 Business — Empower. Educate. Transform."
                                class="block h-auto w-full"
                            >
                        </div>
                    </aside>

                    <div class="flex h-full w-full min-w-0 flex-col justify-center">
                        <div class="mb-8 flex justify-center rounded-lg border-2 border-teal-400 bg-white px-6 py-5 shadow-md shadow-teal-300/30 lg:hidden">
                            <x-brand.mark :href="url('/')" size="xl" align="center" />
                        </div>

                        <div class="flex flex-col justify-center rounded-lg border-2 border-teal-400 bg-white p-8 text-slate-900 shadow-[0_20px_50px_rgba(13,148,136,0.14)] lg:py-10">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
