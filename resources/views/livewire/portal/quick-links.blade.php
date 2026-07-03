<div>
    @if ($hasActions)
        <section class="mb-8">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-700">Quick Links</p>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($actions as $action)
                    @if ($action['type'] === 'link')
                        <a
                            href="{{ $action['href'] }}"
                            @if ($action['navigate'] ?? false) wire:navigate.hover @endif
                            title="{{ $action['description'] ?? $action['label'] }}"
                            class="inline-flex items-center rounded-full border border-teal-200/80 bg-white px-4 py-2 text-sm font-semibold text-teal-800 shadow-sm transition hover:border-teal-300 hover:bg-teal-50 hover:text-teal-950"
                        >
                            {{ $action['label'] }}
                        </a>
                    @else
                        @php
                            $tone = $action['tone'] ?? 'teal';
                            $toneClasses = match ($tone) {
                                'emerald' => 'border-emerald-200/80 bg-emerald-50 text-emerald-900 hover:border-emerald-300 hover:bg-emerald-100',
                                'violet' => 'border-violet-200/80 bg-violet-50 text-violet-900 hover:border-violet-300 hover:bg-violet-100',
                                'blue' => 'border-blue-200/80 bg-blue-50 text-blue-900 hover:border-blue-300 hover:bg-blue-100',
                                'indigo' => 'border-indigo-200/80 bg-indigo-50 text-indigo-900 hover:border-indigo-300 hover:bg-indigo-100',
                                'amber' => 'border-amber-200/80 bg-amber-50 text-amber-900 hover:border-amber-300 hover:bg-amber-100',
                                'rose' => 'border-rose-200/80 bg-rose-50 text-rose-900 hover:border-rose-300 hover:bg-rose-100',
                                'cyan' => 'border-cyan-200/80 bg-cyan-50 text-cyan-900 hover:border-cyan-300 hover:bg-cyan-100',
                                'orange' => 'border-orange-200/80 bg-orange-50 text-orange-900 hover:border-orange-300 hover:bg-orange-100',
                                default => 'border-teal-200/80 bg-teal-50 text-teal-900 hover:border-teal-300 hover:bg-teal-100',
                            };
                        @endphp
                        <button
                            type="button"
                            wire:click="{{ $action['action'] }}"
                            class="inline-flex items-center rounded-full border px-4 py-2 text-sm font-semibold shadow-sm transition {{ $toneClasses }}"
                        >
                            {{ $action['label'] }}
                        </button>
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
