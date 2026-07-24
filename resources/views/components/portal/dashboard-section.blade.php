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

    <div @class([
        'grid gap-4',
        'grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-6' => $section->key === 'network',
        'grid-cols-1 sm:grid-cols-2 xl:grid-cols-4' => $section->key !== 'network',
    ])>
        @foreach ($section->cards as $card)
            <x-portal.stat-card :card="$card" />
        @endforeach
    </div>
</section>
