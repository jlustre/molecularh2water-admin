<div>
    @if ($hasActions)
        <section class="mb-8">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-700">Quick Links</p>
            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-8">
                @foreach ($actions as $action)
                    @php
                        $tone = $action['tone'] ?? 'red';
                        $description = $action['description'] ?? $action['label'];
                        $gradientClasses = match ($tone) {
                            'red' => 'from-red-950 via-red-700 to-red-600 hover:from-red-900 hover:via-red-600 hover:to-red-500',
                            'orange' => 'from-orange-950 via-orange-700 to-orange-500 hover:from-orange-900 hover:via-orange-600 hover:to-orange-400',
                            'yellow' => 'from-yellow-950 via-yellow-600 to-yellow-500 hover:from-yellow-900 hover:via-yellow-500 hover:to-yellow-400',
                            'green' => 'from-green-950 via-green-700 to-green-600 hover:from-green-900 hover:via-green-600 hover:to-green-500',
                            'blue' => 'from-blue-950 via-blue-700 to-blue-600 hover:from-blue-900 hover:via-blue-600 hover:to-blue-500',
                            'indigo' => 'from-indigo-950 via-indigo-700 to-indigo-600 hover:from-indigo-900 hover:via-indigo-600 hover:to-indigo-500',
                            'violet' => 'from-violet-950 via-violet-700 to-violet-600 hover:from-violet-900 hover:via-violet-600 hover:to-violet-500',
                            default => 'from-red-950 via-red-700 to-red-600 hover:from-red-900 hover:via-red-600 hover:to-red-500',
                        };
                    @endphp

                    @if ($action['type'] === 'link')
                        <div class="group relative">
                            <a
                                href="{{ $action['href'] }}"
                                @if ($action['navigate'] ?? false) wire:navigate.hover @endif
                                title="{{ $description }}"
                                aria-label="{{ $action['label'] }}: {{ $description }}"
                                class="flex min-h-[4.5rem] w-full items-center justify-center rounded-xl bg-gradient-to-br px-3 py-4 text-center text-sm font-bold leading-5 text-white shadow-md transition duration-200 hover:-translate-y-0.5 hover:shadow-lg {{ $gradientClasses }}"
                            >
                                <span class="line-clamp-2">{{ $action['label'] }}</span>
                            </a>
                            <div class="pointer-events-none absolute bottom-[calc(100%+0.55rem)] left-1/2 z-30 w-56 -translate-x-1/2 rounded-lg border border-slate-200 bg-slate-950 px-3 py-2 text-left opacity-0 shadow-xl transition duration-150 group-hover:opacity-100">
                                <p class="text-[11px] font-black uppercase tracking-[0.14em] text-teal-300">{{ $action['label'] }}</p>
                                <p class="mt-1 text-xs font-medium leading-5 text-white/90">{{ $description }}</p>
                            </div>
                        </div>
                    @else
                        <div class="group relative">
                            <button
                                type="button"
                                wire:click="{{ $action['action'] }}"
                                title="{{ $description }}"
                                aria-label="{{ $action['label'] }}: {{ $description }}"
                                class="flex min-h-[4.5rem] w-full items-center justify-center rounded-xl bg-gradient-to-br px-3 py-4 text-center text-sm font-bold leading-5 text-white shadow-md transition duration-200 hover:-translate-y-0.5 hover:shadow-lg {{ $gradientClasses }}"
                            >
                                <span class="line-clamp-2">{{ $action['label'] }}</span>
                            </button>
                            <div class="pointer-events-none absolute bottom-[calc(100%+0.55rem)] left-1/2 z-30 w-56 -translate-x-1/2 rounded-lg border border-slate-200 bg-slate-950 px-3 py-2 text-left opacity-0 shadow-xl transition duration-150 group-hover:opacity-100">
                                <p class="text-[11px] font-black uppercase tracking-[0.14em] text-teal-300">{{ $action['label'] }}</p>
                                <p class="mt-1 text-xs font-medium leading-5 text-white/90">{{ $description }}</p>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    @endif

    <livewire:portal.registration-invites-modal />
    <livewire:portal.prospects-modal />
    <livewire:portal.demos-modal />
    <livewire:portal.phone-calls-modal />
    <livewire:portal.meetings-modal />
    <livewire:portal.appointments-modal />
    <livewire:portal.tasks-modal />
    <livewire:portal.referrals-modal />
</div>
