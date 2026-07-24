@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        @if (session('status'))
            <div class="rounded-md border border-teal-100 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
                {{ session('status') }}
            </div>
        @endif

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">System</p>
                    <h1 class="mt-2 text-3xl font-black tracking-normal text-slate-950">Settings</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Manage branding, contact details, portal links, notification defaults, and admin appearance.
                    </p>
                </div>
                <a
                    href="{{ route('admin.website-content.edit') }}"
                    class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-teal-50 px-4 py-2.5 text-sm font-bold text-teal-800 transition hover:bg-teal-100"
                >
                    Website Content
                </a>
            </div>
        </section>

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Branding</h2>
                <p class="mt-1 text-sm text-slate-500">Public-facing company identity.</p>
                <div class="mt-5">
                    <label for="site_company_name" class="block text-sm font-semibold text-slate-700">Company name</label>
                    <input
                        id="site_company_name"
                        name="site_company_name"
                        type="text"
                        required
                        maxlength="255"
                        value="{{ old('site_company_name', $settings['site.company_name']) }}"
                        class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                    >
                    @error('site_company_name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </section>

            <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Contact</h2>
                <p class="mt-1 text-sm text-slate-500">Support channels shown to members and prospects.</p>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="site_support_email" class="block text-sm font-semibold text-slate-700">Support email</label>
                        <input
                            id="site_support_email"
                            name="site_support_email"
                            type="email"
                            maxlength="255"
                            value="{{ old('site_support_email', $settings['site.support_email']) }}"
                            class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        >
                        @error('site_support_email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="site_support_phone" class="block text-sm font-semibold text-slate-700">Support phone</label>
                        <input
                            id="site_support_phone"
                            name="site_support_phone"
                            type="text"
                            maxlength="50"
                            value="{{ old('site_support_phone', $settings['site.support_phone']) }}"
                            class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        >
                        @error('site_support_phone')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Portal</h2>
                <p class="mt-1 text-sm text-slate-500">Links used in member portal demos and invites.</p>
                <div class="mt-5">
                    <label for="portal_online_demo_link" class="block text-sm font-semibold text-slate-700">Online demo link</label>
                    <input
                        id="portal_online_demo_link"
                        name="portal_online_demo_link"
                        type="url"
                        maxlength="500"
                        value="{{ old('portal_online_demo_link', $settings['portal.online_demo_link']) }}"
                        class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        placeholder="https://zoom.us/j/..."
                    >
                    @error('portal_online_demo_link')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </section>

            <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Notifications</h2>
                <p class="mt-1 text-sm text-slate-500">Default from name and address for outbound email.</p>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="notifications_from_name" class="block text-sm font-semibold text-slate-700">From name</label>
                        <input
                            id="notifications_from_name"
                            name="notifications_from_name"
                            type="text"
                            maxlength="255"
                            value="{{ old('notifications_from_name', $settings['notifications.from_name']) }}"
                            class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        >
                        @error('notifications_from_name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="notifications_from_email" class="block text-sm font-semibold text-slate-700">From email</label>
                        <input
                            id="notifications_from_email"
                            name="notifications_from_email"
                            type="email"
                            maxlength="255"
                            value="{{ old('notifications_from_email', $settings['notifications.from_email']) }}"
                            class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        >
                        @error('notifications_from_email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Appearance</h2>
                <p class="mt-1 text-sm text-slate-500">Admin shell sidebar design variant.</p>
                <div class="mt-5">
                    <label for="sidebar_design" class="block text-sm font-semibold text-slate-700">Sidebar design</label>
                    <select
                        id="sidebar_design"
                        name="sidebar_design"
                        required
                        class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                    >
                        @foreach ($sidebarDesigns as $value => $label)
                            <option value="{{ $value }}" @selected(old('sidebar_design', $settings['ui.sidebar_design']) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('sidebar_design')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </section>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-2.5 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
@endsection
