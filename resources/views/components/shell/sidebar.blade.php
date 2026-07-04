@php
    use App\Support\Navigation\AppNavigation;

    $links = AppNavigation::links();
    $sections = AppNavigation::sections();
    $activeSections = AppNavigation::activeSections();
    $user = auth()->user();
    $userName = $user?->name ?? 'User';
    $userEmail = $user?->email ?? '';
@endphp

{{-- Nav-only panel; geometry from [data-shell-sidebar] in shell layout (top: header height, below brand). --}}
{{-- Text/icon colors: explicit CSS in layouts/shell.blade.php (.shell-nav-*), not Tailwind opacity utilities. --}}
<section class="flex h-full w-full flex-col overflow-hidden" style="background:linear-gradient(180deg,#041f1e 0%,#062926 60%,#031a19 100%);position:relative;font-family:ui-sans-serif,system-ui,sans-serif;border-right:1px solid rgba(45,212,191,0.12);">
    <!-- Decorative glows -->
    <div style="position:absolute;top:-60px;right:-60px;width:200px;height:200px;border-radius:50%;background:radial-gradient(circle,rgba(20,184,166,0.12) 0%,transparent 70%);pointer-events:none;"></div>
    <div style="position:absolute;bottom:120px;left:-40px;width:140px;height:140px;border-radius:50%;background:radial-gradient(circle,rgba(13,148,136,0.1) 0%,transparent 70%);pointer-events:none;"></div>
    <div style="position:absolute;top:0;right:0;width:1px;height:100%;background:linear-gradient(180deg,transparent,rgba(20,184,166,0.25) 30%,rgba(20,184,166,0.12) 70%,transparent);pointer-events:none;"></div>

    <nav
        aria-label="Application navigation"
        class="relative flex-1 space-y-4 overflow-y-auto px-3 py-4"
        style="scrollbar-width:thin;scrollbar-color:rgba(45,212,191,0.35) transparent;"
        x-data="sidebarNavGroups('appNavGroups', @js($activeSections))"
    >
        @foreach ($sections as $section => $label)
            @php $sectionLinks = collect($links)->where('section', $section); @endphp
            @if ($sectionLinks->isNotEmpty())
                <div>
                    <button
                        type="button"
                        class="shell-nav-section-btn flex w-full items-center justify-between rounded-md px-2 py-1 text-left transition"
                        @click="toggle('{{ $section }}')"
                        :aria-expanded="isOpen('{{ $section }}')"
                    >
                        <span class="shell-nav-section-label text-[10px] font-semibold uppercase tracking-[0.12em]">{{ $label }}</span>
                        <svg
                            class="shell-nav-section-chevron size-3 shrink-0 transition-transform duration-200"
                            :class="{ '-rotate-90': ! isOpen('{{ $section }}') }"
                            fill="none"
                            viewBox="0 0 12 12"
                            aria-hidden="true"
                        >
                            <path d="M2.5 4.5 6 8 9.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div class="mt-1 space-y-0.5" x-show="isOpen('{{ $section }}')">
                        @foreach ($sectionLinks as $link)
                            <a
                                href="{{ $link['href'] }}"
                                @if ($link['wire_navigate']) wire:navigate @endif
                                @if ($link['active']) aria-current="page" @endif
                                @class([
                                    'shell-nav-link mb-0.5 flex items-center gap-2.5 rounded-lg border px-2.5 py-2 text-[13.5px] font-normal transition',
                                    'is-active' => $link['active'],
                                ])
                            >
                                <span class="flex size-5 shrink-0 items-center justify-center">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                        <circle cx="8" cy="8" r="5.5" stroke="{{ $link['active'] ? '#2dd4bf' : 'rgba(45,212,191,0.6)' }}" stroke-width="1.2"/>
                                    </svg>
                                </span>
                                <span class="min-w-0 flex-1 truncate">{{ $link['label'] }}</span>
                                @if (! empty($link['badge']))
                                    @php
                                        $badgeToneClass = match ($link['badge_tone'] ?? null) {
                                            'live' => 'is-live',
                                            'warn' => 'is-warn',
                                            default => '',
                                        };
                                    @endphp
                                    <span @class(['shell-nav-badge rounded-full border px-1.5 py-0.5 text-[10px] font-semibold', $badgeToneClass])>
                                        {{ $link['badge'] }}
                                    </span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </nav>

    <div class="relative border-t p-3" style="border-color:rgba(153,246,228,0.12);background:rgba(3,26,25,0.6);">
        <div class="shell-nav-profile rounded-[10px] border p-3">
            <div class="mb-2.5 flex items-center gap-2.5">
                <div class="relative shrink-0">
                    <div class="flex size-9 items-center justify-center rounded-full" style="border:1.5px solid rgba(94,234,212,0.45);background:linear-gradient(135deg,#063f3a,#0a6b63);">
                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                            <circle cx="9" cy="6.5" r="3" fill="rgba(45,212,191,0.75)"/>
                            <path d="M3 15C3 12.24 5.69 10 9 10C12.31 10 15 12.24 15 15" stroke="rgba(45,212,191,0.75)" stroke-width="1.3" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="absolute bottom-0 right-0 size-2.5 rounded-full" style="border:1.5px solid #041f1e;background:#10b981;"></div>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="shell-nav-profile-name truncate text-[13px] font-medium leading-tight">{{ $userName }}</div>
                    <div class="shell-nav-profile-email mt-px truncate text-[10.5px]">{{ $userEmail }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    aria-label="Sign out"
                    class="shell-nav-sign-out flex w-full items-center justify-center gap-1.5 rounded-[7px] border px-2 py-1.5 text-xs font-medium transition"
                >
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                        <path d="M5.5 7H12.5M12.5 7L10 4.5M12.5 7L10 9.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8.5 2H3C2.45 2 2 2.45 2 3V11C2 11.55 2.45 12 3 12H8.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                    </svg>
                    Sign Out
                </button>
            </form>
        </div>
    </div>
</section>
