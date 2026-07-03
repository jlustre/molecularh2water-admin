@props([
    'section',
])

<section class="mb-8">
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-700">{{ $section->title }}</p>
            @if ($section->description)
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">{{ $section->description }}</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($section->cards as $card)
            <x-portal.stat-card :card="$card" />
        @endforeach
    </div>
</section>
