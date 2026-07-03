@php
    $paths = [
        'mail' => '<path d="M3.5 5.5h11a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1h-11a1 1 0 0 1-1-1v-7a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.5"/><path d="m4.5 6.5 4.5 3.5 4.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
        'badge' => '<circle cx="9" cy="6.5" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M4 14.5c0-2.49 2.24-4.5 5-4.5s5 2.01 5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'calendar' => '<rect x="3.5" y="4.5" width="11" height="10" rx="1.5" stroke="currentColor" stroke-width="1.5"/><path d="M6.5 3.5v2M11.5 3.5v2M3.5 7.5h11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'layers' => '<path d="M9 3 3.5 6 9 9l5.5-3L9 3Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="m3.5 9 5.5 3 5.5-3M3.5 12l5.5 3 5.5-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
        'users' => '<circle cx="6" cy="7" r="2.2" stroke="currentColor" stroke-width="1.5"/><circle cx="12.5" cy="8" r="2" stroke="currentColor" stroke-width="1.5"/><path d="M2.5 14.5c0-1.93 1.57-3.5 3.5-3.5M12 14.5c0-1.66 1.34-3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'ticket' => '<path d="M4.5 6.5h9v1.2a1.5 1.5 0 0 0 0 3V12h-9v-1.3a1.5 1.5 0 0 1 0-3V6.5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>',
        'user-plus' => '<circle cx="7" cy="7.5" r="2.5" stroke="currentColor" stroke-width="1.5"/><path d="M3 14.5c0-2.21 1.79-4 4-4M12.5 7.5H15M13.75 5.75v3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'folder' => '<path d="M4 5.5h4l1.2 1.5H14a1 1 0 0 1 1 1v6.5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6.5a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>',
        'play' => '<circle cx="9" cy="9" r="5.5" stroke="currentColor" stroke-width="1.5"/><path d="m7.5 7.2 4 1.8-4 1.8V7.2Z" fill="currentColor"/>',
        'document' => '<path d="M6 3.5h5.2L14 6.3v8.2a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.5"/><path d="M11 3.5v3h3" stroke="currentColor" stroke-width="1.5"/>',
        'pencil' => '<path d="M11.5 4.5 13.5 6.5 6.5 13.5 4 14l.5-2.5 7-7Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>',
        'sparkles' => '<path d="m9 2.5 1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>',
        'bell' => '<path d="M9 3.5a3 3 0 0 1 3 3v2.2c0 .74.3 1.45.83 1.97L13.5 13H4.5l.67-2.33A2.8 2.8 0 0 0 5.5 8.7V6.5a3 3 0 0 1 3-3Z" stroke="currentColor" stroke-width="1.5"/><path d="M7.5 13a1.5 1.5 0 0 0 3 0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'chart' => '<path d="M4 13.5V8M7.5 13.5V5.5M11 13.5V9M14.5 13.5V4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M3.5 13.5h11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'fire' => '<path d="M9 3.5s2.5 2.2 2.5 4.8c0 1.4-.7 2.2-1.5 2.7.6-.2 1.3-.8 1.3-1.8 0-2.2-2-3.7-2-3.7S7.3 6.8 7.3 9.2c0 1 .7 1.6 1.3 1.8-.8-.5-1.5-1.3-1.5-2.7 0-2.6 2.5-4.8 2.5-4.8Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M9 14.5c1.7 0 3-1.1 3-2.7S9 9.5 9 9.5s-3 .8-3 2.3 1.3 2.7 3 2.7Z" stroke="currentColor" stroke-width="1.5"/>',
        'check' => '<rect x="3.5" y="4.5" width="11" height="11" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="m6.5 9 1.8 1.8L12 7.1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
        'alert' => '<path d="M9 3.5 15 14.5H3L9 3.5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M9 8.5v3M9 13h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
    ];
@endphp

<svg width="20" height="20" viewBox="0 0 18 18" fill="none" aria-hidden="true">
    {!! $paths[$icon] ?? $paths['chart'] !!}
</svg>
