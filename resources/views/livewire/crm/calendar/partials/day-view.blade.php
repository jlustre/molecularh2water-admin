@php
    $dayEntries = $entries->filter(fn ($e) =>
        ($e->span_start ?? $e->start_at->copy()->startOfDay())->lte($focus->copy()->startOfDay())
        && ($e->span_end ?? ($e->end_at ?? $e->start_at)->copy()->startOfDay())->gte($focus->copy()->startOfDay())
    )->sortBy([
        fn ($e) => ! empty($e->is_all_day) || ! empty($e->is_bar) ? 0 : 1,
        fn ($e) => $e->start_at?->timestamp ?? 0,
    ]);
@endphp

<div class="space-y-3">
    @forelse ($dayEntries as $entry)
        <button
            type="button"
            class="flex w-full items-start gap-4 rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50/80 p-4 text-left hover:border-teal-300 hover:from-teal-50/50 hover:to-emerald-50/40"
            wire:click="openDetails('{{ $entry->kind }}', {{ $entry->id }})"
        >
            <div class="w-28 shrink-0 text-sm font-bold text-teal-700">
                @if (! empty($entry->is_all_day))
                    All day
                @elseif (! empty($entry->spans_multiple_days))
                    Multi-day
                @else
                    {{ $entry->start_at->format('g:i A') }}
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $typeColors[$entry->color] ?? 'bg-teal-100 text-teal-800' }}">
                        {{ $entry->type_name }}
                    </span>
                    <span class="text-xs uppercase text-slate-500">{{ $entry->status }}</span>
                </div>
                <p class="mt-1 text-lg font-semibold text-slate-900">{{ $entry->title }}</p>
                @if ($entry->user_name)
                    <p class="text-sm text-slate-500">{{ $entry->user_name }}</p>
                @endif
            </div>
        </button>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 bg-gradient-to-br from-slate-50/80 to-white p-10 text-center text-sm text-slate-500">
            No events scheduled for this day.
        </div>
    @endforelse
</div>
