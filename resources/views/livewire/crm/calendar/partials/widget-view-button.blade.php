@props(['kind', 'id'])

<button
    {{ $attributes->merge([
        'type' => 'button',
        'class' => 'shrink-0 rounded-lg border border-teal-200/80 bg-teal-50 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-teal-700 hover:bg-teal-100',
        'title' => 'View details',
    ]) }}
    wire:click.stop="openDetails('{{ $kind }}', {{ $id }})"
>
    View
</button>
