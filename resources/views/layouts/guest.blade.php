<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Molecular H2 Water Admin') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#041f1e] font-sans text-white antialiased selection:bg-teal-300 selection:text-[#031a19]">
        <main class="relative min-h-screen overflow-hidden">
            <div class="absolute inset-0 bg-[linear-gradient(135deg,#041f1e_0%,#062926_48%,#031a19_100%)]"></div>
            <div class="absolute inset-0 opacity-[0.08] [background-image:linear-gradient(rgba(45,212,191,.85)_1px,transparent_1px),linear-gradient(90deg,rgba(45,212,191,.85)_1px,transparent_1px)] [background-size:42px_42px]"></div>
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-teal-300/70 to-transparent"></div>

            <section class="relative mx-auto grid min-h-screen w-full max-w-6xl items-center gap-8 px-6 py-8 lg:grid-cols-[0.92fr_1.08fr] lg:px-10">
                <aside class="hidden min-h-[640px] flex-col justify-between rounded-lg border border-teal-200/[0.16] bg-white/[0.06] p-8 shadow-[0_28px_70px_rgba(0,0,0,0.28)] backdrop-blur-xl lg:flex">
                    <a href="{{ url('/') }}" class="group flex items-center gap-3 rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-300" wire:navigate>
                        <span class="flex size-12 items-center justify-center rounded-full border border-teal-300/[0.45] bg-teal-300/10 shadow-[0_0_24px_rgba(45,212,191,0.18)]">
                            <span class="font-mono text-lg font-bold tracking-tight text-teal-200">H2</span>
                        </span>
                        <span>
                            <span class="block text-sm font-semibold tracking-wide text-white">Molecular H2 Water</span>
                            <span class="block text-xs font-semibold uppercase tracking-[0.22em] text-teal-200/70">Admin Portal</span>
                        </span>
                    </a>

                    <div>
                        <p class="inline-flex items-center gap-2 rounded-full border border-teal-300/25 bg-white/[0.07] px-3 py-1 text-xs font-bold uppercase tracking-[0.22em] text-teal-100">
                            <span class="size-2 rounded-full bg-teal-300 shadow-[0_0_14px_rgba(45,212,191,0.9)]"></span>
                            Secure Access
                        </p>
                        <h1 class="mt-7 text-4xl font-black leading-tight tracking-normal text-white">
                            One calm control point for your admin work.
                        </h1>
                        <p class="mt-5 text-sm leading-7 text-teal-50/[0.72]">
                            Sign in to manage leads, appointments, content, customer messages, and system settings in the same teal operations environment.
                        </p>
                    </div>

                    <div class="rounded-md border border-white/10 bg-[#f8fbfb] p-4 text-slate-900">
                        <div class="mb-5 flex items-center gap-2">
                            <span class="size-2.5 rounded-full bg-teal-500"></span>
                            <span class="size-2.5 rounded-full bg-amber-400"></span>
                            <span class="size-2.5 rounded-full bg-slate-300"></span>
                            <span class="ml-2 h-2.5 flex-1 rounded-full bg-teal-900/[0.08]"></span>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="rounded-md border border-slate-200 bg-white p-3 shadow-sm">
                                <div class="h-2 w-10 rounded-full bg-slate-300"></div>
                                <div class="mt-4 h-6 w-8 rounded bg-slate-900"></div>
                            </div>
                            <div class="rounded-md border border-slate-200 bg-white p-3 shadow-sm">
                                <div class="h-2 w-12 rounded-full bg-slate-300"></div>
                                <div class="mt-4 h-6 w-8 rounded bg-slate-900"></div>
                            </div>
                            <div class="rounded-md border border-slate-200 bg-white p-3 shadow-sm">
                                <div class="h-2 w-9 rounded-full bg-slate-300"></div>
                                <div class="mt-4 h-6 w-12 rounded bg-teal-600"></div>
                            </div>
                        </div>
                        <div class="mt-4 rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="mb-4 h-3 w-24 rounded-full bg-slate-800"></div>
                            <div class="space-y-3">
                                <div class="h-3 rounded-full bg-teal-500/70"></div>
                                <div class="h-3 w-10/12 rounded-full bg-teal-400/50"></div>
                                <div class="h-3 w-8/12 rounded-full bg-teal-300/60"></div>
                            </div>
                        </div>
                    </div>
                </aside>

                <div class="mx-auto w-full max-w-md">
                    <div class="mb-8 flex justify-center lg:hidden">
                        <a href="{{ url('/') }}" class="flex items-center gap-3 rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-300" wire:navigate>
                            <span class="flex size-12 items-center justify-center rounded-full border border-teal-300/[0.45] bg-teal-300/10 shadow-[0_0_24px_rgba(45,212,191,0.18)]">
                                <span class="font-mono text-lg font-bold tracking-tight text-teal-200">H2</span>
                            </span>
                            <span>
                                <span class="block text-sm font-semibold tracking-wide text-white">Molecular H2 Water</span>
                                <span class="block text-xs font-semibold uppercase tracking-[0.22em] text-teal-200/70">Admin Portal</span>
                            </span>
                        </a>
                    </div>

                    <div class="overflow-hidden rounded-lg border border-teal-200/[0.18] bg-white/[0.96] p-6 text-slate-900 shadow-[0_28px_70px_rgba(0,0,0,0.32)] sm:p-8">
                        {{ $slot }}
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
