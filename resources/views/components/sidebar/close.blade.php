<button
    type="button"
    @click="closeSidebar()"
    aria-label="Close sidebar"
    {{ $attributes->merge(['class' => 'flex size-10 shrink-0 items-center justify-center rounded-full shadow transition focus:outline-none focus:ring-2 focus:ring-teal-400 lg:hidden']) }}
>
    <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
    </svg>
</button>
