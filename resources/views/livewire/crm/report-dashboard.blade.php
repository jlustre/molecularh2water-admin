<div class="p-4 sm:p-6 lg:p-8">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-600">Analytics</p>
            <h1 class="mt-1 text-3xl font-bold text-slate-900">Reports & Performance</h1>
            <p class="mt-1 text-sm text-slate-500">Lead sources, funnel health, consultant activity, and capture trends.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <select class="rounded-xl border-slate-200 text-sm shadow-sm" wire:model.live="period">
                <option value="7d">Last 7 days</option>
                <option value="30d">Last 30 days</option>
                <option value="90d">Last 90 days</option>
                <option value="all">All time</option>
            </select>
            <button
                class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                type="button"
                wire:click="exportCsv"
            >
                Export CSV
            </button>
            @if (auth()->user()?->hasPermission('crm.dashboard.view'))
                <a
                    class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                    href="{{ route(\App\Support\Crm\CrmRoutes::name('dashboard.index')) }}"
                >
                    Executive Dashboard
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ([
            ['label' => 'Total Records', 'value' => $summary['total_records'] ?? 0],
            ['label' => 'Prospects', 'value' => $summary['prospects'] ?? 0],
            ['label' => 'Customers', 'value' => $summary['clients'] ?? 0],
            ['label' => 'Closed Won', 'value' => $summary['closed_won'] ?? 0],
            ['label' => 'Conversion Rate', 'value' => ($summary['conversion_rate'] ?? 0).'%'],
            ['label' => 'Activities Logged', 'value' => $summary['activities_logged'] ?? 0],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">
                    {{ is_numeric($card['value']) ? number_format($card['value']) : $card['value'] }}
                </p>
            </div>
        @endforeach
    </div>

    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Total Revenue', 'value' => '$'.number_format($executive['totalRevenue'] ?? 0, 2)],
            ['label' => 'Demo Success Rate', 'value' => ($executive['demoSuccess']['rate'] ?? 0).'%'],
            ['label' => 'Referral Conversion', 'value' => ($executive['referralConversion']['rate'] ?? 0).'%'],
            ['label' => 'Completed Demos', 'value' => $executive['demoSuccess']['completed'] ?? 0],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">
                    {{ is_numeric($card['value']) ? number_format($card['value']) : $card['value'] }}
                </p>
            </div>
        @endforeach
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">Avg. Stage Duration</h2>
            <div class="mt-4 space-y-3">
                @forelse (($executive['stageDurations'] ?? collect()) as $row)
                    <div>
                        <div class="mb-1 flex justify-between text-sm">
                            <span class="font-medium text-slate-700">{{ $row->stage }}</span>
                            <span class="text-slate-500">{{ $row->avg_days }} days</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-orange-400" style="width: {{ min(100, max(4, $row->avg_days * 5)) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Stage history will appear as leads move through the pipeline.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">Revenue by Product</h2>
            <div class="mt-4 space-y-3">
                @php($productRows = $executive['revenueByProduct'] ?? collect())
                @php($maxProduct = max($productRows->max('revenue') ?? 1, 1))
                @forelse ($productRows as $row)
                    <div>
                        <div class="mb-1 flex justify-between text-sm">
                            <span class="font-medium text-slate-700">{{ $row->label }}</span>
                            <span class="text-slate-500">${{ number_format($row->revenue, 2) }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-gradient-to-r from-teal-500 to-emerald-500" style="width: {{ max(4, ($row->revenue / $maxProduct) * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Paid order line items will appear here.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">Lead Sources</h2>
            <div class="mt-4 space-y-3">
                @forelse ($leadSources as $source)
                    <div>
                        <div class="mb-1 flex justify-between text-sm">
                            <span class="font-medium text-slate-700">{{ $source->label }}</span>
                            <span class="text-slate-500">{{ $source->count }} ({{ $source->percentage }}%)</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-gradient-to-r from-cyan-500 to-teal-500" style="width: {{ max(4, $source->percentage) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No source data for this period.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">Funnel Conversion</h2>
            <div class="mt-4 space-y-3">
                @foreach ($funnelStages as $stage)
                    @if ($stage->count > 0)
                        <div>
                            <div class="mb-1 flex justify-between text-sm">
                                <span class="font-medium text-slate-700">
                                    {{ $stage->name }}
                                    @if ($stage->is_won)
                                        <span class="text-emerald-600">· Won</span>
                                    @elseif ($stage->is_lost)
                                        <span class="text-rose-600">· Lost</span>
                                    @endif
                                </span>
                                <span class="text-slate-500">{{ $stage->count }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-gradient-to-r from-teal-500 to-emerald-500" style="width: {{ max(4, $stage->percentage) }}%"></div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">Monthly Capture Trend</h2>
            <div class="mt-4 flex items-end gap-2" style="min-height: 180px">
                @php($maxTrend = max($monthlyTrend->max('count') ?? 1, 1))
                @foreach ($monthlyTrend as $point)
                    <div class="flex flex-1 flex-col items-center justify-end gap-2">
                        <span class="text-xs font-semibold text-slate-600">{{ $point->count }}</span>
                        <div
                            class="w-full rounded-t-lg bg-gradient-to-t from-teal-600 to-emerald-400"
                            style="height: {{ max(8, ($point->count / $maxTrend) * 140) }}px"
                        ></div>
                        <span class="text-[10px] text-slate-500">{{ $point->label }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($agentLeaderboard->isNotEmpty())
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Consultant Leaderboard</h2>
                <table class="mt-4 min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="pb-2">Consultant</th>
                            <th class="pb-2">Leads</th>
                            <th class="pb-2">Closed</th>
                            <th class="pb-2">Activities</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($agentLeaderboard as $row)
                            <tr>
                                <td class="py-2 font-semibold text-slate-900">{{ $row->name }}</td>
                                <td class="py-2 text-slate-600">{{ $row->leads }}</td>
                                <td class="py-2 text-slate-600">{{ $row->closed }}</td>
                                <td class="py-2 text-slate-600">{{ $row->activities }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif ($referralLeaderboard->isNotEmpty())
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Referral Leaderboard</h2>
                <table class="mt-4 min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="pb-2">Client</th>
                            <th class="pb-2">Referrals</th>
                            <th class="pb-2">Converted</th>
                            <th class="pb-2">Rewarded</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($referralLeaderboard as $row)
                            <tr>
                                <td class="py-2 font-semibold text-slate-900">{{ $row->name }}</td>
                                <td class="py-2 text-slate-600">{{ $row->referrals }}</td>
                                <td class="py-2 text-slate-600">{{ $row->converted }}</td>
                                <td class="py-2 text-slate-600">{{ $row->rewarded }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if ($agentLeaderboard->isNotEmpty() && $referralLeaderboard->isNotEmpty())
        <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">Referral Leaderboard</h2>
            <table class="mt-4 min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="pb-2">Client</th>
                        <th class="pb-2">Referrals</th>
                        <th class="pb-2">Converted</th>
                        <th class="pb-2">Rewarded</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($referralLeaderboard as $row)
                        <tr>
                            <td class="py-2 font-semibold text-slate-900">{{ $row->name }}</td>
                            <td class="py-2 text-slate-600">{{ $row->referrals }}</td>
                            <td class="py-2 text-slate-600">{{ $row->converted }}</td>
                            <td class="py-2 text-slate-600">{{ $row->rewarded }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($landingPages->isNotEmpty())
        <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">Landing Page Performance</h2>
            <table class="mt-4 min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="pb-2">Page</th>
                        <th class="pb-2">Slug</th>
                        <th class="pb-2">Conversions</th>
                        <th class="pb-2">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($landingPages as $page)
                        <tr>
                            <td class="py-2 font-semibold text-slate-900">{{ $page->title }}</td>
                            <td class="py-2 text-slate-600">{{ $page->slug }}</td>
                            <td class="py-2 text-slate-600">{{ number_format($page->conversions) }}</td>
                            <td class="py-2">
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-800' => $page->published,
                                    'bg-slate-100 text-slate-600' => ! $page->published,
                                ])>{{ $page->published ? 'Published' : 'Draft' }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
