@props(['active' => false, 'href' => '#'])

<a href="{{ $href }}"
   class="flex items-center px-4 py-2 rounded-md transition-colors
          {{ $active ? 'bg-cyan-100 text-cyan-700 font-semibold' : 'text-gray-700 hover:bg-cyan-50 hover:text-cyan-700' }}">
    @if (isset($icon))
        <span class="mr-3">
            {{ $icon }}
        </span>
    @endif
    <span>{{ $slot }}</span>
</a>
