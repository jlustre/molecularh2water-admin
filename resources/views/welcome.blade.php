<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Molecular H2 Water Admin') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#041f1e] font-sans text-white antialiased selection:bg-teal-300 selection:text-[#031a19]">
        <main class="relative min-h-screen overflow-hidden">
            <div class="absolute inset-0 bg-[linear-gradient(135deg,#041f1e_0%,#062926_46%,#031a19_100%)]"></div>
            <div class="absolute inset-0 opacity-[0.08] [background-image:linear-gradient(rgba(45,212,191,.85)_1px,transparent_1px),linear-gradient(90deg,rgba(45,212,191,.85)_1px,transparent_1px)] [background-size:42px_42px]"></div>
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-teal-300/70 to-transparent"></div>

            <section class="relative mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6 sm:px-8 lg:px-10">
                <header class="flex items-center justify-between gap-4">
                    <a href="{{ url('/') }}" class="group flex items-center gap-3 rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-300">
                        <span class="flex size-12 items-center justify-center rounded-full border border-teal-300/[0.45] bg-teal-300/10 shadow-[0_0_24px_rgba(45,212,191,0.18)]">
                            <span class="font-mono text-lg font-bold tracking-tight text-teal-200">H2</span>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold tracking-wide text-white">Molecular H2 Water</span>
                            <span class="block text-xs font-semibold uppercase tracking-[0.22em] text-teal-200/70">Admin Portal</span>
                        </span>
                    </a>

                    @if (Route::has('login'))
                        <nav class="flex items-center gap-2">
                            @auth
                                <a href="{{ url('/admin/dashboard') }}" class="rounded-md border border-teal-200/25 bg-white/[0.08] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:border-teal-200/50 hover:bg-white/[0.12] focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-300">
                                    Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="rounded-md px-4 py-2 text-sm font-semibold text-teal-50 transition hover:bg-white/[0.08] focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-300">
                                    Login
                                </a>

                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="rounded-md bg-teal-400 px-4 py-2 text-sm font-bold text-[#031a19] shadow-[0_12px_30px_rgba(45,212,191,0.22)] transition hover:bg-teal-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
                                        Register
                                    </a>
                                @endif
                            @endauth
                        </nav>
                    @endif
                </header>

                <div class="grid w-full min-w-0 flex-1 items-center gap-12 py-14 lg:grid-cols-[1.02fr_0.98fr] lg:py-10">
                    <div class="w-full min-w-0 max-w-full lg:max-w-3xl">
                        <p class="inline-flex items-center gap-2 rounded-full border border-teal-300/25 bg-white/[0.07] px-3 py-1 text-xs font-bold uppercase tracking-[0.22em] text-teal-100">
                            <span class="size-2 rounded-full bg-teal-300 shadow-[0_0_14px_rgba(45,212,191,0.9)]"></span>
                            Secure Operations
                        </p>

                        <h1 class="mt-8 max-w-4xl text-3xl font-black leading-[1.08] tracking-normal text-white sm:text-6xl sm:leading-[1.02] lg:text-7xl">
                            Manage the Molecular H2 Water experience from one clean portal.
                        </h1>

                        <p class="mt-6 max-w-2xl text-base leading-8 text-teal-50/[0.72] sm:text-lg">
                            A focused admin entry point for leads, appointments, content, users, and customer conversations with the same teal control-room feel as your dashboard.
                        </p>

                        <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                            @guest
                                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-6 py-3 text-sm font-bold text-[#031a19] shadow-[0_18px_42px_rgba(45,212,191,0.25)] transition hover:bg-teal-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
                                    Login to Admin
                                </a>

                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-md border border-teal-200/30 bg-white/[0.08] px-6 py-3 text-sm font-bold text-white transition hover:border-teal-100/60 hover:bg-white/[0.12] focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-300">
                                        Create Account
                                    </a>
                                @endif
                            @else
                                <a href="{{ url('/admin/dashboard') }}" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-6 py-3 text-sm font-bold text-[#031a19] shadow-[0_18px_42px_rgba(45,212,191,0.25)] transition hover:bg-teal-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
                                    Open Dashboard
                                </a>
                            @endguest
                        </div>

                        <dl class="mt-12 grid max-w-2xl grid-cols-3 gap-3">
                            <div class="border-l border-teal-300/30 pl-4">
                                <dt class="text-2xl font-extrabold text-white">28</dt>
                                <dd class="mt-1 text-xs font-semibold uppercase tracking-[0.16em] text-teal-100/[0.62]">Leads</dd>
                            </div>
                            <div class="border-l border-teal-300/30 pl-4">
                                <dt class="text-2xl font-extrabold text-white">7</dt>
                                <dd class="mt-1 text-xs font-semibold uppercase tracking-[0.16em] text-teal-100/[0.62]">Messages</dd>
                            </div>
                            <div class="border-l border-teal-300/30 pl-4">
                                <dt class="text-2xl font-extrabold text-white">Live</dt>
                                <dd class="mt-1 text-xs font-semibold uppercase tracking-[0.16em] text-teal-100/[0.62]">Portal</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="relative min-w-0">
                        <div class="rounded-lg border border-teal-200/[0.18] bg-white/[0.06] p-3 shadow-[0_28px_70px_rgba(0,0,0,0.32)] backdrop-blur-xl">
                            <div class="overflow-hidden rounded-md border border-white/10 bg-[#f8fbfb] text-slate-900">
                                <div class="flex items-center gap-3 border-b border-teal-900/10 bg-white/80 px-4 py-3">
                                    <div class="flex gap-1.5">
                                        <span class="size-2.5 rounded-full bg-teal-500"></span>
                                        <span class="size-2.5 rounded-full bg-amber-400"></span>
                                        <span class="size-2.5 rounded-full bg-slate-300"></span>
                                    </div>
                                    <div class="h-2.5 flex-1 rounded-full bg-teal-900/[0.08]"></div>
                                    <div class="size-8 rounded-full bg-gradient-to-br from-teal-300 to-teal-700"></div>
                                </div>

                                <div class="grid min-h-[440px] grid-cols-[96px_1fr] sm:grid-cols-[148px_1fr]">
                                    <aside class="bg-[#041f1e] p-4">
                                        <div class="mb-6 flex items-center gap-2">
                                            <div class="flex size-9 items-center justify-center rounded-full border border-teal-300/40 bg-teal-300/10 text-xs font-bold text-teal-200">H2</div>
                                            <div class="hidden sm:block">
                                                <div class="h-2 w-20 rounded-full bg-white/[0.85]"></div>
                                                <div class="mt-2 h-1.5 w-14 rounded-full bg-teal-300/[0.45]"></div>
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <div class="h-8 rounded-md border border-teal-300/[0.35] bg-teal-300/[0.15]"></div>
                                            <div class="h-8 rounded-md bg-white/[0.07]"></div>
                                            <div class="h-8 rounded-md bg-white/[0.07]"></div>
                                            <div class="h-8 rounded-md bg-white/[0.07]"></div>
                                        </div>

                                        <div class="mt-7 space-y-2">
                                            <div class="h-2 w-16 rounded-full bg-teal-300/30"></div>
                                            <div class="h-8 rounded-md bg-white/[0.07]"></div>
                                            <div class="h-8 rounded-md bg-white/[0.07]"></div>
                                            <div class="h-8 rounded-md bg-white/[0.07]"></div>
                                        </div>
                                    </aside>

                                    <section class="p-5 sm:p-6">
                                        <div class="mb-6 flex items-center justify-between gap-4">
                                            <div>
                                                <div class="h-4 w-36 rounded-full bg-slate-900/[0.85]"></div>
                                                <div class="mt-2 h-2.5 w-52 max-w-full rounded-full bg-teal-900/[0.12]"></div>
                                            </div>
                                            <div class="hidden h-10 w-32 rounded-full bg-teal-600 sm:block"></div>
                                        </div>

                                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                            <div class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                                                <div class="h-2.5 w-16 rounded-full bg-slate-300"></div>
                                                <div class="mt-5 h-7 w-12 rounded bg-slate-900"></div>
                                            </div>
                                            <div class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                                                <div class="h-2.5 w-14 rounded-full bg-slate-300"></div>
                                                <div class="mt-5 h-7 w-10 rounded bg-slate-900"></div>
                                            </div>
                                            <div class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                                                <div class="h-2.5 w-20 rounded-full bg-slate-300"></div>
                                                <div class="mt-5 h-7 w-10 rounded bg-slate-900"></div>
                                            </div>
                                            <div class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                                                <div class="h-2.5 w-12 rounded-full bg-slate-300"></div>
                                                <div class="mt-5 h-7 w-10 rounded bg-slate-900"></div>
                                            </div>
                                        </div>

                                        <div class="mt-5 grid gap-4 lg:grid-cols-[1.4fr_0.8fr]">
                                            <div class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                                                <div class="mb-5 flex items-center justify-between">
                                                    <div class="h-3 w-28 rounded-full bg-slate-800"></div>
                                                    <div class="h-7 w-20 rounded-full bg-teal-100"></div>
                                                </div>
                                                <div class="space-y-3">
                                                    <div class="h-3 rounded-full bg-teal-500/70"></div>
                                                    <div class="h-3 w-10/12 rounded-full bg-teal-400/50"></div>
                                                    <div class="h-3 w-8/12 rounded-full bg-teal-300/60"></div>
                                                    <div class="h-3 w-11/12 rounded-full bg-slate-200"></div>
                                                </div>
                                            </div>

                                            <div class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                                                <div class="h-3 w-24 rounded-full bg-slate-800"></div>
                                                <div class="mt-5 space-y-3">
                                                    <div class="flex items-center gap-3">
                                                        <div class="size-8 rounded-full bg-teal-100"></div>
                                                        <div class="h-2.5 flex-1 rounded-full bg-slate-200"></div>
                                                    </div>
                                                    <div class="flex items-center gap-3">
                                                        <div class="size-8 rounded-full bg-amber-100"></div>
                                                        <div class="h-2.5 flex-1 rounded-full bg-slate-200"></div>
                                                    </div>
                                                    <div class="flex items-center gap-3">
                                                        <div class="size-8 rounded-full bg-cyan-100"></div>
                                                        <div class="h-2.5 flex-1 rounded-full bg-slate-200"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </section>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
