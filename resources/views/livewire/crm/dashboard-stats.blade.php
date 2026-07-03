<div>
    {{-- CRM dashboard stats --}}
    <div class="mb-8 overflow-hidden rounded-2xl bg-gradient-to-r from-[#041f1e] via-[#062926] to-[#0a3d38] p-6 text-white shadow-lg sm:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-300/80">Field Sales CRM</p>
                <h2 class="mt-1 text-2xl font-bold sm:text-3xl">Sales Command Center</h2>
                <p class="mt-2 max-w-2xl text-sm text-teal-100/80">
                    Track prospects, book cooking and water awareness shows, and stay on top of demos and follow-ups.
                </p>
            </div>
            @if (auth()->user()?->hasPermission('leads.view'))
                <a
                    class="inline-flex items-center justify-center rounded-full bg-white/10 px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 transition hover:bg-white/20"
                    href="{{ route(\App\Support\Crm\CrmRoutes::name('leads.index')) }}"
                >
                    View Leads
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Total Leads', 'value' => $totalLeads, 'hint' => 'All contacts in CRM'],
            ['label' => 'New Leads (7d)', 'value' => $newLeads, 'hint' => 'Captured this week'],
            ['label' => 'Hot Prospects', 'value' => $hotProspects, 'hint' => 'High-intent prospects'],
            ['label' => 'Follow-Ups Today', 'value' => $followUpsDueToday, 'hint' => 'Due for outreach'],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($card['value']) }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $card['hint'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Scheduled Demos', 'value' => $scheduledDemos, 'hint' => 'In demo pipeline stages'],
            ['label' => 'Demos Today', 'value' => $demosToday, 'hint' => 'On the calendar today'],
            ['label' => 'Active Prospects', 'value' => $activeProspects, 'hint' => 'In prospect lifecycle'],
            ['label' => 'Pending Quotes', 'value' => $pendingQuotes, 'hint' => 'Quote presented stage'],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($card['value']) }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $card['hint'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Appointments Today', 'value' => $appointmentsToday],
            ['label' => 'Pending Orders', 'value' => $pendingOrders],
            ['label' => 'Closed Sales (Month)', 'value' => $closedSales],
            ['label' => 'Conversion Rate', 'value' => $conversionRate.'%'],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ is_numeric($card['value']) ? number_format($card['value']) : $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-bold text-slate-900">Pipeline Summary</h3>
                @if (auth()->user()?->hasPermission('pipeline.view'))
                    <a class="text-sm font-semibold text-teal-700 hover:text-teal-900" href="{{ route(\App\Support\Crm\CrmRoutes::name('pipeline.index')) }}">
                        Open Board
                    </a>
                @endif
            </div>
            @if ($funnelStages->isEmpty())
                <p class="text-sm text-slate-500">Run migrations and seed CRM data to populate pipeline metrics.</p>
            @else
                <div class="space-y-3">
                    @foreach ($funnelStages as $stage)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium text-slate-700">{{ $stage->name }}</span>
                                <span class="text-slate-500">{{ $stage->leads_count }} leads</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-teal-500 to-emerald-500"
                                    style="width: {{ $totalLeads > 0 ? max(4, ($stage->leads_count / max($totalLeads, 1)) * 100) : 0 }}%"
                                ></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900">Upcoming Tasks</h3>
            <div class="mt-4 space-y-3">
                @forelse ($upcomingTasks as $task)
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                        <p class="text-sm font-semibold text-slate-800">{{ $task->title }}</p>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $task->due_at?->format('M j, g:i A') ?? 'No due date' }}
                            @if ($task->lead)
                                · {{ $task->lead->fullName() }}
                            @endif
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No pending tasks.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-bold text-slate-900">Recent Activity</h3>
        <div class="mt-4 divide-y divide-slate-100">
            @forelse ($recentActivities as $activity)
                <div class="flex items-start justify-between gap-4 py-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">
                            {{ $activity->title ?? 'Activity logged' }}
                        </p>
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
</div>
