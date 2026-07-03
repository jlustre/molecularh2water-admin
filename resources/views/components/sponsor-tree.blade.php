@props(['nodes', 'level' => 0])

<ul @class([
    'space-y-2',
    'ml-0' => $level === 0,
    'ml-5 border-l border-slate-200 pl-4' => $level > 0,
])>
    @foreach ($nodes as $node)
        <li>
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <p class="font-semibold text-slate-900">{{ $node['name'] }}</p>
                <p class="text-xs text-slate-500">{{ $node['email'] }}</p>
            </div>
            @if (! empty($node['children']))
                <div class="mt-2">
                    <x-sponsor-tree :nodes="$node['children']" :level="$level + 1" />
                </div>
            @endif
        </li>
    @endforeach
</ul>
