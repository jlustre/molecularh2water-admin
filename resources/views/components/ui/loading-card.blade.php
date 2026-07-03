@props([
    'message' => 'Loading...',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center gap-3 rounded-2xl bg-white px-8 py-6 shadow-xl ring-1 ring-slate-200/80']) }}>
    <x-ui.spinner size="lg" />
    <p class="text-sm font-semibold text-slate-600">{{ $message }}</p>
</div>
