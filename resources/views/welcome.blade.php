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
    <body class="min-h-screen bg-white font-sans text-slate-900 antialiased selection:bg-teal-200 selection:text-slate-900">
        <main class="relative min-h-screen overflow-hidden bg-[#f8fbfb]">
            <div class="absolute inset-0 opacity-40 [background-image:linear-gradient(rgba(13,148,136,.07)_1px,transparent_1px),linear-gradient(90deg,rgba(13,148,136,.07)_1px,transparent_1px)] [background-size:42px_42px]"></div>
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-teal-300/30 to-transparent"></div>

            <section class="relative mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6 sm:px-8 lg:px-10">
                <header class="flex items-center justify-between gap-4 rounded-lg border border-teal-100 bg-white px-5 py-4 shadow-sm">
                    <x-brand.mark :href="url('/')" size="xl" :navigate="false" />

                    @if (Route::has('login'))
                        <nav class="flex items-center gap-2">
                            @auth
                                <a href="{{ url('/admin/dashboard') }}" class="rounded-md border border-teal-200 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-teal-300 hover:bg-teal-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-400">
                                    Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="rounded-md px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-teal-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-400">
                                    Login
                                </a>

                                @if (Route::has('register') && ! config('registration.invite_only'))
                                    <a href="{{ route('register') }}" class="rounded-md bg-teal-500 px-4 py-2 text-sm font-bold text-white shadow-[0_12px_30px_rgba(13,148,136,0.22)] transition hover:bg-teal-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-400">
                                        Register
                                    </a>
                                @endif
                            @endauth
                        </nav>
                    @endif
                </header>

                <div class="grid w-full min-w-0 flex-1 items-center gap-12 py-14 lg:grid-cols-[1.02fr_0.98fr] lg:py-10">
                    <div class="w-full min-w-0 max-w-full lg:max-w-3xl">
                        <p class="inline-flex items-center gap-2 rounded-full border border-teal-200 bg-teal-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.22em] text-teal-800">
                            <span class="size-2 rounded-full bg-teal-500 shadow-[0_0_10px_rgba(13,148,136,0.35)]"></span>
                            Secure Operations
                        </p>

                        <h1 class="mt-8 max-w-4xl text-3xl font-black leading-[1.08] tracking-normal text-slate-900 sm:text-6xl sm:leading-[1.02] lg:text-7xl">
                            Manage the Molecular H2 Water experience from one clean portal.
                        </h1>

                        <p class="mt-6 max-w-2xl text-base leading-8 text-slate-600 sm:text-lg">
                            A focused admin entry point for leads, appointments, content, users, and customer conversations with the same teal control-room feel as your dashboard.
                        </p>

                        <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                            @guest
                                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-md bg-teal-500 px-6 py-3 text-sm font-bold text-white shadow-[0_18px_42px_rgba(13,148,136,0.22)] transition hover:bg-teal-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-400">
                                    Login to Admin
                                </a>

                                @if (Route::has('register') && ! config('registration.invite_only'))
                                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-6 py-3 text-sm font-bold text-slate-800 transition hover:border-teal-300 hover:bg-teal-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-400">
                                        Create Account
                                    </a>
                                @endif
                            @else
                                <a href="{{ url('/admin/dashboard') }}" class="inline-flex items-center justify-center rounded-md bg-teal-500 px-6 py-3 text-sm font-bold text-white shadow-[0_18px_42px_rgba(13,148,136,0.22)] transition hover:bg-teal-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-400">
                                    Open Dashboard
                                </a>
                            @endguest
                        </div>
                    </div>

                    <div class="relative min-w-0">
                        <div class="overflow-hidden rounded-2xl border border-teal-100 bg-white p-2 shadow-[0_20px_50px_rgba(15,23,42,0.08)] sm:p-3">
                            <img
                                alt="Managing My H2 Business — leads, demos, sales funnel, and growth tools"
                                class="h-auto w-full rounded-xl object-cover object-center"
                                src="{{ asset('images/welcome/h2-business-hero.png') }}"
                            />
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
