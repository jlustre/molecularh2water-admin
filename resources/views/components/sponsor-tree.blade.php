@props([
    'nodes',
    'level' => 0,
    'linkable' => true,
])

<ul @class([
    'space-y-2',
    'ml-0' => $level === 0,
    'ml-5 border-l border-slate-200 pl-4' => $level > 0,
])>
    @foreach ($nodes as $node)
        <li>
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm transition hover:border-teal-200 hover:shadow-md">
                @if ($linkable && ! empty($node['id']))
                    <a href="{{ route('portal.team.member', $node['id']) }}" @if (! request()->is('admin/*')) wire:navigate @endif class="block">
                        <p class="font-semibold text-slate-900 hover:text-teal-700">{{ $node['name'] }}</p>
                        <p class="text-xs text-slate-500">{{ $node['email'] }}</p>
                        <p class="mt-1 text-[11px] font-semibold uppercase tracking-wide text-teal-700">View overview →</p>
                    </a>
                @else
                    <p class="font-semibold text-slate-900">{{ $node['name'] }}</p>
                    <p class="text-xs text-slate-500">{{ $node['email'] }}</p>
                @endif
            </div>
            @if (! empty($node['children']))
                <div class="mt-2">
                    <x-sponsor-tree :nodes="$node['children']" :level="$level + 1" :linkable="$linkable" />
                </div>
            @endif
        </li>
    @endforeach
</ul>
