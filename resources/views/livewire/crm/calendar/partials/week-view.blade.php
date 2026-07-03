@php
    $weekStart = $focus->copy()->startOfWeek();
    $days = collect(range(0, 6))->map(fn ($i) => $weekStart->copy()->addDays($i));
@endphp

<div class="grid gap-4 md:grid-cols-7">
    @foreach ($days as $day)
        @php $dayEntries = $entries->filter(fn ($e) => $e->start_at->isSameDay($day)); @endphp
        <div @class([
            'rounded-xl border bg-gradient-to-br from-white to-slate-50/80 p-3',
            'border-teal-300 from-teal-50/80 via-white to-emerald-50/50' => $day->isToday(),
            'border-slate-200' => ! $day->isToday(),
        ])>
            <p class="text-xs font-bold uppercase text-slate-500">{{ $day->format('D j') }}</p>
            <div class="mt-2 space-y-2">
                @forelse ($dayEntries as $entry)
                    <button
                        type="button"
                        class="block w-full rounded-lg border px-2 py-1.5 text-left text-xs font-semibold {{ $typeColors[$entry->color] ?? 'bg-teal-100 text-teal-800 border-teal-200' }}"
                        wire:click="openDetails('{{ $entry->kind }}', {{ $entry->id }})"
                    >
                        <span class="block">{{ $entry->start_at->format('g:i A') }}</span>
                        <span class="block truncate">{{ $entry->title }}</span>
                    </button>
                @empty
                    <p class="text-xs text-slate-400">No events</p>
                @endforelse
            </div>
        </div>
    @endforeach
</div>
