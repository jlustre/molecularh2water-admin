<div class="divide-y divide-slate-100">
    @php $grouped = $entries->groupBy(fn ($e) => $e->start_at->format('Y-m-d')); @endphp
    @forelse ($grouped as $date => $dayEntries)
        <div class="py-4">
            <h3 class="text-sm font-bold text-slate-900">{{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}</h3>
            <div class="mt-3 space-y-2">
                @foreach ($dayEntries as $entry)
                    <button
                        type="button"
                        class="flex w-full items-center justify-between rounded-xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50/70 px-4 py-3 text-left hover:border-teal-200 hover:from-teal-50/40 hover:to-emerald-50/30"
                        wire:click="openDetails('{{ $entry->kind }}', {{ $entry->id }})"
                    >
                        <div>
                            <p class="font-semibold text-slate-900">{{ $entry->title }}</p>
                            <p class="text-xs text-slate-500">{{ $entry->type_name }} · {{ $entry->start_at->format('g:i A') }}</p>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $typeColors[$entry->color] ?? 'bg-teal-100 text-teal-800' }}">
                            {{ ucfirst($entry->kind) }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 bg-gradient-to-br from-slate-50/80 to-white py-10 text-center text-sm text-slate-500">No events in this range.</div>
    @endforelse
</div>
