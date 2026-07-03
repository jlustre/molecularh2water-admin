<div class="relative p-4 sm:p-6 lg:p-8" data-portal-member-hierarchy-scope>
    <x-portal.page-loading-overlay scope="data-portal-member-hierarchy-scope" message="Loading hierarchy..." :fullscreen="true" />

    <div class="mb-6">
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-600">Sponsor Network</p>
        <h2 class="mt-1 text-2xl font-bold text-slate-900">Member hierarchy</h2>
        <p class="mt-1 max-w-2xl text-sm text-slate-600">
            Your sponsor chain and everyone you have personally sponsored.
        </p>
    </div>

    <div class="mb-6 grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Your sponsor</p>
            <p class="mt-2 text-lg font-bold text-slate-900">
                {{ auth()->user()?->sponsor?->name ?? 'None (top-level account)' }}
            </p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Upline depth</p>
            <p class="mt-2 text-lg font-bold text-slate-900">{{ count($upline) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Downline members</p>
            <p class="mt-2 text-lg font-bold text-slate-900">{{ number_format($downlineCount) }}</p>
        </div>
    </div>

    @if (count($upline) > 0)
        <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Upline</h3>
            <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                @foreach ($upline as $member)
                    <span class="rounded-full bg-teal-50 px-3 py-1 font-semibold text-teal-800">{{ $member['name'] }}</span>
                    @if (! $loop->last)
                        <span class="text-slate-400">→</span>
                    @endif
                @endforeach
                <span class="text-slate-400">→</span>
                <span class="rounded-full bg-slate-900 px-3 py-1 font-semibold text-white">{{ auth()->user()?->name }}</span>
            </div>
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm">
        <h3 class="mb-4 text-lg font-bold text-slate-900">Your organization</h3>
        <x-sponsor-tree :nodes="$tree" />
    </div>
</div>
