@props([
    'roleLabel' => null,
])

@php
    $user = auth()->user();
    $userName = $user?->name ?? 'User';
    $userEmail = $user?->email ?? '';
    $initials = collect(explode(' ', trim($userName)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->join('');
    $initials = $initials !== '' ? mb_strtoupper($initials) : 'U';
    $avatarUrl = $user?->avatarUrl();
    $roleLabel = $roleLabel ?? match (true) {
        $user?->hasRole('super-admin') => 'Super Admin',
        $user?->hasRole('admin') => 'Administrator',
        $user?->hasRole('team-admin') => 'Team Admin',
        $user?->hasRole('manager') => 'Manager',
        $user?->hasRole('consultant') => 'Consultant',
        default => 'Member',
    };
@endphp

<details
    {{ $attributes->merge(['class' => 'group relative shrink-0']) }}
    x-data="{
        avatarUrl: @js($avatarUrl),
        userName: @js($userName),
        initials: @js($initials),
        initialsFrom(name) {
            const parts = String(name || '').trim().split(/\s+/).filter(Boolean).slice(0, 2);

            return parts.map((part) => part.charAt(0)).join('').toUpperCase() || 'U';
        },
    }"
    x-on:profile-updated.window="
        if ($event.detail.avatarUrl !== undefined) {
            avatarUrl = $event.detail.avatarUrl;
        }

        if ($event.detail.name) {
            userName = $event.detail.name;
            initials = initialsFrom(userName);
        }
    "
>
    <summary
        aria-label="Open user menu"
        class="flex cursor-pointer list-none items-center gap-2 rounded-full py-1 pl-1 pr-2 transition hover:bg-teal-50 focus:outline-none focus:ring-2 focus:ring-teal-400 [&::-webkit-details-marker]:hidden"
    >
        <span class="relative flex items-center">
            <img
                x-show="avatarUrl"
                x-cloak
                :src="avatarUrl"
                :alt="userName + ' avatar'"
                class="size-10 rounded-full border-2 border-white object-cover shadow-inner"
            >
            <span
                x-show="! avatarUrl"
                x-text="initials"
                class="inline-flex size-10 items-center justify-center rounded-full border-2 border-white bg-gradient-to-br from-teal-400/80 to-teal-700/80 text-sm font-bold text-white shadow-inner"
            >{{ $initials }}</span>
            <span class="absolute bottom-0 right-0 size-3 rounded-full border-2 border-white bg-emerald-400"></span>
        </span>
        <span class="hidden flex-col items-start sm:flex">
            <span class="max-w-32 truncate text-sm font-semibold leading-tight text-slate-900" x-text="userName">{{ $userName }}</span>
            <span class="text-xs text-teal-700">{{ $roleLabel }}</span>
        </span>
        <svg width="18" height="18" fill="none" viewBox="0 0 18 18" class="text-teal-700 transition group-open:rotate-180"><path d="M6 7l3 3 3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>

    <div class="absolute right-0 top-full z-50 mt-3 w-64 overflow-hidden rounded-lg border border-teal-100 bg-white shadow-xl shadow-teal-950/10">
        <div class="border-b border-teal-50 px-4 py-3">
            <p class="truncate text-sm font-semibold text-slate-900" x-text="userName">{{ $userName }}</p>
            <p class="truncate text-xs text-teal-700">{{ $userEmail }}</p>
        </div>

        <div class="py-2">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-teal-50 hover:text-teal-800">
                <svg width="18" height="18" fill="none" viewBox="0 0 18 18" class="text-teal-600"><rect x="3" y="3" width="12" height="12" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M6 10.5l2.1-2.1 1.8 1.8L12 7.7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                My Dashboard
            </a>
            <a href="{{ route('profile') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-teal-50 hover:text-teal-800">
                <svg width="18" height="18" fill="none" viewBox="0 0 18 18" class="text-teal-600"><circle cx="9" cy="6" r="3" stroke="currentColor" stroke-width="1.6"/><path d="M3.5 15c0-2.49 2.46-4.5 5.5-4.5s5.5 2.01 5.5 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                My Profile
            </a>
            @if ($user?->hasPermission('invites.manage'))
                <a href="{{ route('portal.invites') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-teal-50 hover:text-teal-800">
                    <svg width="18" height="18" fill="none" viewBox="0 0 18 18" class="text-teal-600"><path d="M9 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="1.6"/><path d="M3.5 15.5c0-2.5 2.46-4 5.5-4s5.5 1.5 5.5 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M13.5 3.5 15 5M15 3.5 13.5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    Member Invites
                </a>
            @endif
            @if ($user?->canAccessAdmin())
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-teal-50 hover:text-teal-800">
                    <svg width="18" height="18" fill="none" viewBox="0 0 18 18" class="text-teal-600"><path d="M3 5.5 9 2l6 3.5v7L9 16l-6-3.5v-7Z" stroke="currentColor" stroke-width="1.5"/></svg>
                    Admin Portal
                </a>
                <a href="{{ route('admin.settings') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-teal-50 hover:text-teal-800">
                    <svg width="18" height="18" fill="none" viewBox="0 0 18 18" class="text-teal-600"><circle cx="9" cy="9" r="2.4" stroke="currentColor" stroke-width="1.6"/><path d="M9 2v2M9 14v2M2 9h2M14 9h2M4.05 4.05l1.42 1.42M12.53 12.53l1.42 1.42M4.05 13.95l1.42-1.42M12.53 5.47l1.42-1.42" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    Settings
                </a>
            @endif
        </div>

        <form method="POST" action="{{ route('logout') }}" class="border-t border-teal-50">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm font-semibold text-red-600 transition hover:bg-red-50">
                <svg width="18" height="18" fill="none" viewBox="0 0 18 18" class="text-red-500"><path d="M7 4H4.5A1.5 1.5 0 0 0 3 5.5v7A1.5 1.5 0 0 0 4.5 14H7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M10.5 12.5 14 9l-3.5-3.5M14 9H7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Log off
            </button>
        </form>
    </div>
</details>
