<div class="relative p-4 sm:p-6 lg:p-8" data-portal-member-overview-scope>
    <x-portal.page-loading-overlay scope="data-portal-member-overview-scope" message="Loading member overview..." :fullscreen="true" />

    <div class="mb-4">
        <a href="{{ $backUrl }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-semibold text-teal-700 hover:text-teal-900">
            ← Back
        </a>
    </div>

    <section class="mb-8 overflow-hidden rounded-2xl border border-teal-200/20 bg-gradient-to-br from-[#041f1e] via-[#062926] to-[#0a3d38] p-6 text-white shadow-xl sm:p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-300/80">Member Overview</p>
                <h2 class="mt-2 text-2xl font-black tracking-tight sm:text-3xl">
                    {{ $member->name }}
                </h2>
                <p class="mt-2 text-sm text-teal-100/80">{{ $member->email }}</p>
                @if ($member->sponsor)
                    <p class="mt-2 text-xs text-teal-200/70">
                        Sponsored by <span class="font-semibold text-teal-100">{{ $member->sponsor->name }}</span>
                    </p>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center gap-2 rounded-full border border-teal-300/25 bg-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-teal-100">
                    <span class="size-2 rounded-full bg-teal-300 shadow-[0_0_12px_rgba(45,212,191,0.8)]"></span>
                    {{ $roleLabel }}
                </span>
                <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold text-teal-50/80">
                    {{ now()->format('l, M j') }}
                </span>
            </div>
        </div>
    </section>

    @foreach ($sections as $section)
        <x-portal.dashboard-section :section="$section" />
    @endforeach

    <div class="mb-8 grid gap-4 lg:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Recruits</p>
                    <h3 class="mt-1 text-lg font-bold text-slate-900">Direct members</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ $recruitsPeriodLabel }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach (['week' => 'Week', 'month' => 'Month', 'year' => 'Year'] as $key => $label)
                        <button
                            type="button"
                            wire:click="setRecruitsPeriod('{{ $key }}')"
                            @class([
                                'rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition',
                                'border-teal-600 bg-teal-600 text-white' => $recruitsPeriod === $key,
                                'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' => $recruitsPeriod !== $key,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                    <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-bold text-teal-800">{{ number_format($recruitCount) }}</span>
                </div>
            </div>

            <ul class="divide-y divide-slate-100">
                @forelse ($recruits as $recruit)
                    <li class="flex items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <a
                                href="{{ route('portal.team.member', $recruit) }}"
                                wire:navigate
                                class="truncate text-sm font-semibold text-slate-800 hover:text-teal-700"
                            >
                                {{ $recruit->name }}
                            </a>
                            <p class="truncate text-xs text-slate-500">{{ $recruit->email }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            <a
                                href="{{ route('portal.team.member', $recruit) }}"
                                wire:navigate
                                class="text-xs font-semibold text-teal-700 hover:text-teal-900"
                            >
                                View
                            </a>
                            <p class="mt-1 text-[11px] text-slate-400">{{ $recruit->created_at?->format('M j, Y') }}</p>
                        </div>
                    </li>
                @empty
                    <li class="py-3 text-sm text-slate-500">No direct recruits for {{ strtolower($recruitsPeriodLabel) }}.</li>
                @endforelse
            </ul>

            @if ($recruitCount > $recruits->count())
                <p class="mt-2 text-xs text-slate-500">Showing {{ $recruits->count() }} of {{ number_format($recruitCount) }}.</p>
            @endif
        </section>

        @if ($showSales && $salesSummary)
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">Sales</p>
                        <h3 class="mt-1 text-lg font-bold text-slate-900">{{ $salesPeriodLabel }}</h3>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach (['week' => 'Week', 'month' => 'Month', 'year' => 'Year'] as $key => $label)
                            <button
                                type="button"
                                wire:click="setSalesPeriod('{{ $key }}')"
                                @class([
                                    'rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition',
                                    'border-emerald-600 bg-emerald-600 text-white' => $salesPeriod === $key,
                                    'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' => $salesPeriod !== $key,
                                ])
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                        @if ($salesIndexUrl)
                            <a href="{{ $salesIndexUrl }}" class="text-xs font-semibold text-teal-700 hover:text-teal-900">Open sales</a>
                        @endif
                    </div>
                </div>

                <div class="mb-4 grid grid-cols-3 gap-3">
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Deals</p>
                        <p class="mt-1 text-xl font-black text-slate-900">{{ number_format($salesSummary['count']) }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Completed</p>
                        <p class="mt-1 text-xl font-black text-slate-900">{{ number_format($salesSummary['completed']) }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Volume</p>
                        <p class="mt-1 text-xl font-black text-slate-900">${{ number_format($salesSummary['total'], 0) }}</p>
                    </div>
                </div>

                <ul class="divide-y divide-slate-100">
                    @forelse ($salesSummary['recent'] as $sale)
                        <li class="flex items-start justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ $sale->displayCustomerName() }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $sale->status->label() }}
                                    · ${{ number_format((float) $sale->total, 0) }}
                                </p>
                            </div>
                            <span class="shrink-0 text-xs text-slate-400">{{ $sale->created_at?->format('M j') }}</span>
                        </li>
                    @empty
                        <li class="py-3 text-sm text-slate-500">No sales logged for {{ strtolower($salesPeriodLabel) }}.</li>
                    @endforelse
                </ul>
            </section>
        @endif
    </div>

    @if ($showPerformance)
        <section class="mb-8">
            <div class="mb-4">
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-indigo-700">Performance</p>
                <p class="mt-1 text-sm text-slate-500">Read-only coaching summary for {{ $member->name }}.</p>
            </div>

            <livewire:crm.consultant-performance-summary
                :subject-user-id="$member->id"
                :lock-subject="true"
                :key="'member-perf-summary-'.$member->id"
            />
        </section>
    @endif

    @if ($crmDetail && ($showPipeline || $showEvents || $showTasks || $showActivities))
        <section class="mb-8">
            <div class="mb-4">
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-700">CRM Insights</p>
                <p class="mt-1 text-sm text-slate-500">
                    Pipeline, upcoming demos/presentations, and activity for {{ $member->name }}.
                </p>
            </div>

            @if ($showPipeline || $showEvents || $showTasks)
                @include('livewire.portal.partials.crm-insights-row', [
                    'crmDetail' => $crmDetail,
                    'showPipeline' => $showPipeline,
                    'showEvents' => $showEvents,
                    'showTasks' => $showTasks,
                    'pipelineInteractive' => false,
                    'eventsEmptyMessage' => 'No upcoming shows, demos, or meetings on their calendar.',
                    'tasksEmptyMessage' => 'No upcoming calls, invites, or follow-up tasks scheduled.',
                ])
            @endif

            @if ($showActivities)
                <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-slate-900">Recent Activity</h3>
                    <div class="mt-4 divide-y divide-slate-100">
                        @forelse ($crmDetail['recentActivities'] as $activity)
                            <div class="flex items-start justify-between gap-4 py-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">{{ $activity->title ?? 'Activity logged' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        @if (isset($activity->lead))
                                            {{ $activity->lead?->fullName() ?? 'Contact' }}
                                        @endif
                                        @if (isset($activity->user))
                                            · {{ $activity->user?->name }}
                                        @endif
                                    </p>
                                </div>
                                <span class="shrink-0 text-xs text-slate-400">
                                    {{ $activity->created_at?->diffForHumans() }}
                                </span>
                            </div>
                        @empty
                            <p class="py-3 text-sm text-slate-500">No recent activity for this member yet.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </section>
    @endif
</div>
