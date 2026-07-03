@php

    $links = \App\Support\Portal\PortalNavigation::links();

    $user = auth()->user();

    $userName = $user?->name ?? 'Member';

    $userEmail = $user?->email ?? '';

    $sections = ['workspace' => 'Workspace', 'crm' => 'CRM & Sales', 'account' => 'Account'];

    $activeSections = collect($links)

        ->filter(fn (array $link) => $link['active'])

        ->pluck('section')

        ->unique()

        ->values()

        ->all();

@endphp



<aside class="flex h-full min-h-screen w-72 shrink-0 flex-col border-r border-teal-200/[0.14] bg-[#041f1e]/90 p-5 shadow-[18px_0_50px_rgba(0,0,0,0.18)] backdrop-blur-xl">

    <div class="mb-8 flex items-start justify-between gap-3">
        <x-brand.mark :href="route('dashboard')" portal-label="Associate Portal" size="sidebar" class="min-w-0" />
        <x-sidebar.close class="border border-teal-200/20 bg-white/10 text-teal-100 hover:bg-teal-400/20" />
    </div>



    <nav

        aria-label="Portal navigation"

        class="flex-1 space-y-4 overflow-y-auto"

        x-data="sidebarNavGroups('portalNavGroups', @js($activeSections))"

    >

        @foreach ($sections as $section => $label)

            @php $sectionLinks = collect($links)->where('section', $section); @endphp

            @if ($sectionLinks->isNotEmpty())

                <div>

                    <button

                        type="button"

                        class="flex w-full items-center justify-between rounded-md px-3 py-1 text-left transition hover:bg-white/[0.05]"

                        @click="toggle('{{ $section }}')"

                        :aria-expanded="isOpen('{{ $section }}')"

                    >

                        <span class="text-xs font-bold uppercase tracking-[0.22em] text-teal-200/45">{{ $label }}</span>

                        <svg

                            class="size-4 shrink-0 text-teal-200/45 transition-transform duration-200"

                            :class="{ '-rotate-90': ! isOpen('{{ $section }}') }"

                            fill="none"

                            viewBox="0 0 20 20"

                            aria-hidden="true"

                        >

                            <path d="M5 7.5 10 12.5 15 7.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" />

                        </svg>

                    </button>

                    <div class="mt-1 space-y-1" x-show="isOpen('{{ $section }}')">

                        @foreach ($sectionLinks as $link)

                            <a

                                href="{{ $link['href'] }}"

                                @if (! str_contains($link['href'], '/admin')) wire:navigate @endif

                                @class([

                                    'flex items-center rounded-md border px-3 py-2.5 text-sm font-semibold transition',

                                    'border-teal-300/35 bg-teal-300/15 text-white' => $link['active'],

                                    'border-transparent text-teal-50/70 hover:bg-white/[0.07] hover:text-white' => ! $link['active'],

                                ])

                            >

                                {{ $link['label'] }}

                            </a>

                        @endforeach

                    </div>

                </div>

            @endif

        @endforeach

    </nav>



    <div class="rounded-lg border border-teal-200/[0.14] bg-white/[0.05] p-4">

        <p class="text-sm font-semibold text-white">{{ $userName }}</p>

        <p class="mt-1 truncate text-xs text-teal-100/55">{{ $userEmail }}</p>

    </div>

</aside>

