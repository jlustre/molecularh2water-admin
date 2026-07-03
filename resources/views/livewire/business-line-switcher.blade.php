<div>
    @if ($showSwitcher)
        <div class="flex items-center gap-1 rounded-full border border-slate-200 bg-white p-1 shadow-sm" role="group" aria-label="Business line filter">
            @foreach ($lines as $line)
                @php
                    $color = $lineConfig[$line->value]['color'] ?? 'slate';
                    $activeClasses = match ($color) {
                        'orange' => 'bg-orange-500 text-white',
                        'cyan' => 'bg-cyan-600 text-white',
                        default => 'bg-slate-800 text-white',
                    };
                @endphp
                <button
                    type="button"
                    wire:click="select('{{ $line->value }}')"
                    @class([
                        'rounded-full px-3 py-1.5 text-xs font-bold uppercase tracking-wide transition',
                        $active === $line->value ? $activeClasses : 'text-slate-600 hover:bg-slate-100',
                    ])
                >
                    {{ $line->shortLabel() }}
                </button>
            @endforeach
        </div>
    @elseif (count($lines) === 1)
        @php $line = $lines[0]; @endphp
        <span @class([
            'inline-flex rounded-full px-3 py-1.5 text-xs font-bold uppercase tracking-wide',
            ($lineConfig[$line->value]['color'] ?? 'slate') === 'orange' ? 'bg-orange-100 text-orange-800' : 'bg-cyan-100 text-cyan-800',
        ])>
            {{ $line->shortLabel() }}
        </span>
    @endif
</div>
