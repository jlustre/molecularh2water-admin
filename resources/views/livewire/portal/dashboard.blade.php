<div class="relative p-4 sm:p-6 lg:p-8" data-portal-dashboard-scope>
    <x-portal.page-loading-overlay scope="data-portal-dashboard-scope" message="Refreshing dashboard..." :fullscreen="true" />

    <section class="mb-8 overflow-hidden rounded-2xl border border-teal-200/20 bg-gradient-to-br from-[#041f1e] via-[#062926] to-[#0a3d38] p-6 text-white shadow-xl sm:p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-300/80">Associate Portal</p>
                <h2 class="mt-2 text-2xl font-black tracking-tight sm:text-3xl">
                    Welcome back, {{ auth()->user()?->name }}
                </h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-teal-100/80">
                    {{ $headline }}
                </p>
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

    <livewire:portal.quick-links />

    @foreach ($sections as $section)
        <x-portal.dashboard-section :section="$section" />
    @endforeach

    @if ($crmDetail && ($showPipeline || $showEvents || $showTasks || $showActivities))
        <section class="mb-8">
            <div class="mb-4">
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-700">CRM Insights</p>
                <p class="mt-1 text-sm text-slate-500">Pipeline health, scheduled events, and action tasks synced from your calendar.</p>
            </div>

            @if ($showPipeline || $showEvents || $showTasks)
                @include('livewire.portal.partials.crm-insights-row', [
                    'crmDetail' => $crmDetail,
                    'showPipeline' => $showPipeline,
                    'showEvents' => $showEvents,
                    'showTasks' => $showTasks,
                ])
            @endif

            @if ($showPipeline)
                <livewire:portal.pipeline-stage-leads-modal />
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
                            <p class="py-3 text-sm text-slate-500">Activity will appear here as your team works leads.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </section>
    @endif
</div>
