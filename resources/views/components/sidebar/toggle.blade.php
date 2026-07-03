<button
    type="button"
    @click="toggleSidebar()"
    :aria-label="sidebarOpen ? 'Hide sidebar' : 'Show sidebar'"
    :aria-expanded="sidebarOpen"
    {{ $attributes->merge(['class' => 'relative z-[60] flex size-10 shrink-0 items-center justify-center rounded-full bg-white/60 shadow transition hover:bg-teal-100/60 focus:outline-none focus:ring-2 focus:ring-teal-400 lg:z-auto']) }}
>
    <svg
        class="text-teal-700"
        width="24"
        height="24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        viewBox="0 0 24 24"
        aria-hidden="true"
        :class="sidebarOpen ? 'hidden' : ''"
    >
        <rect x="4" y="6" width="16" height="2" rx="1" fill="currentColor" />
        <rect x="4" y="11" width="16" height="2" rx="1" fill="currentColor" />
        <rect x="4" y="16" width="16" height="2" rx="1" fill="currentColor" />
    </svg>
    <svg
        class="hidden text-teal-700"
        width="24"
        height="24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        viewBox="0 0 24 24"
        aria-hidden="true"
        :class="sidebarOpen ? '!block' : ''"
    >
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
    </svg>
</button>
