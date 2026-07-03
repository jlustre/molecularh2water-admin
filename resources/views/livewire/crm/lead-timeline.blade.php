<div>
    @forelse ($entries as $entry)
        <div class="relative border-l-2 border-slate-200 pb-6 pl-6 last:pb-0" wire:key="entry-{{ $entry->type }}-{{ $entry->created_at?->timestamp }}">
            <span @class([
                'absolute -left-[9px] top-1 h-4 w-4 rounded-full border-2 border-white',
                'bg-teal-500' => $entry->type === 'event',
                'bg-amber-500' => $entry->type === 'note',
                'bg-cyan-500' => $entry->type === 'activity',
            ])></span>
            <div class="flex flex-wrap items-center gap-2">
                <p class="text-sm font-bold text-slate-900">{{ $entry->title }}</p>
                <span class="text-xs text-slate-400">{{ $entry->created_at?->diffForHumans() }}</span>
            </div>
            @if ($entry->user)
                <p class="mt-0.5 text-xs text-slate-500">by {{ $entry->user }}</p>
            @endif
            @if ($entry->body)
                <p class="mt-2 whitespace-pre-wrap text-sm text-slate-600">{{ $entry->body }}</p>
            @endif
        </div>
    @empty
        <p class="text-sm text-slate-500">No activity yet. Add a note or update this record to start the timeline.</p>
    @endforelse
</div>
