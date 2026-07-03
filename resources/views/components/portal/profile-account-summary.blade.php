@props([
    'cards',
])

<div class="rounded-lg border border-teal-200/[0.14] bg-white/[0.05] p-4">
    <p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-200/55">Account Overview</p>
    <dl class="mt-3 divide-y divide-teal-200/10">
        @foreach ($cards as $card)
            <div @class(['flex items-start justify-between gap-3 py-2.5 first:pt-0 last:pb-0'])>
                <dt class="shrink-0 text-[11px] font-bold uppercase tracking-[0.14em] text-teal-200/45">{{ $card->label }}</dt>
                <dd class="min-w-0 text-right">
                    <p class="text-sm font-bold leading-5 text-white">{{ $card->value }}</p>
                    @if ($card->hint)
                        <p class="mt-0.5 truncate text-[11px] leading-4 text-teal-100/45" title="{{ $card->hint }}">{{ $card->hint }}</p>
                    @endif
                </dd>
            </div>
        @endforeach
    </dl>
</div>
