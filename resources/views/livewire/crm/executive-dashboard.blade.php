<div class="p-4 sm:p-6 lg:p-8">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-600">Executive</p>
            <h1 class="mt-1 text-3xl font-bold text-slate-900">CRM Dashboard</h1>
            <p class="mt-1 text-sm text-slate-500">Demo performance, pipeline velocity, referrals, and revenue.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <select class="rounded-xl border-slate-200 text-sm shadow-sm" wire:model.live="period">
                <option value="7d">Last 7 days</option>
                <option value="30d">Last 30 days</option>
                <option value="90d">Last 90 days</option>
                <option value="all">All time</option>
            </select>
            @if (auth()->user()?->hasPermission('reports.view'))
                <a
                    class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                    href="{{ route(\App\Support\Crm\CrmRoutes::name('reports.index')) }}"
                >
                    Full Reports
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Total Revenue', 'value' => '$'.number_format($executive['totalRevenue'], 2)],
            ['label' => 'Demo Success Rate', 'value' => $executive['demoSuccess']['rate'].'%'],
            ['label' => 'Referral Conversion', 'value' => $executive['referralConversion']['rate'].'%'],
            ['label' => 'Closed Sales (Month)', 'value' => number_format($quickStats['closedSales'] ?? 0)],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">Revenue Trend</h2>
            <p class="mt-1 text-xs text-slate-500">Paid orders by month</p>
            <div class="mt-4 flex items-end gap-2" style="min-height: 180px">
                @php($maxRevenue = max($executive['revenueTrend']->max('revenue') ?? 1, 1))
                @foreach ($executive['revenueTrend'] as $point)
                    <div class="flex flex-1 flex-col items-center justify-end gap-2">
                        <span class="text-[10px] font-semibold text-slate-600">${{ number_format($point->revenue, 0) }}</span>
                        <div
                            class="w-full rounded-t-lg bg-gradient-to-t from-emerald-600 to-teal-400"
                            style="height: {{ $point->revenue > 0 ? max(8, ($point->revenue / $maxRevenue) * 140) : 4 }}px"
                        ></div>
                        <span class="text-[10px] text-slate-500">{{ $point->label }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">Demo Success</h2>
            <p class="mt-1 text-xs text-slate-500">{{ $executive['demoSuccess']['successful'] }} of {{ $executive['demoSuccess']['completed'] }} completed demos marked interested or sold</p>
            <div class="mt-6">
                <div class="mb-2 flex justify-between text-sm">
                    <span class="font-medium text-slate-700">Success rate</span>
                    <span class="text-slate-500">{{ $executive['demoSuccess']['rate'] }}%</span>
                </div>
                <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-cyan-500 to-teal-500"
                        style="width: {{ max(4, $executive['demoSuccess']['rate']) }}%"
                    ></div>
                </div>
            </div>
            <div class="mt-6">
                <div class="mb-2 flex justify-between text-sm">
                    <span class="font-medium text-slate-700">Referral conversion</span>
                    <span class="text-slate-500">{{ $executive['referralConversion']['converted'] }} / {{ $executive['referralConversion']['total'] }}</span>
                </div>
                <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-blue-500 to-indigo-500"
                        style="width: {{ max(4, $executive['referralConversion']['rate']) }}%"
                    ></div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">Avg. Stage Duration</h2>
            <p class="mt-1 text-xs text-slate-500">Days spent before moving to the next stage</p>
            <div class="mt-4 space-y-3">
                @forelse ($executive['stageDurations'] as $row)
                    <div>
                        <div class="mb-1 flex justify-between text-sm">
                            <span class="font-medium text-slate-700">{{ $row->stage }}</span>
                            <span class="text-slate-500">{{ $row->avg_days }} days · {{ $row->moves }} moves</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            @php($width = min(100, max(4, $row->avg_days * 5)))
                            <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-orange-400" style="width: {{ $width }}%"></div>
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
                @forelse ($executive['revenueByProduct'] as $row)
                    @php($maxProduct = max($executive['revenueByProduct']->max('revenue') ?? 1, 1))
                    <div>
                        <div class="mb-1 flex justify-between text-sm">
                            <span class="font-medium text-slate-700">{{ $row->label }}</span>
                            <span class="text-slate-500">${{ number_format($row->revenue, 2) }} · {{ $row->quantity }} sold</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-teal-500 to-emerald-500"
                                style="width: {{ max(4, ($row->revenue / $maxProduct) * 100) }}%"
                            ></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Paid order line items will appear here.</p>
                @endforelse
            </div>
        </div>
    </div>

    @if ($executive['revenueByAgent']->isNotEmpty())
        <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">Revenue by Consultant</h2>
            <table class="mt-4 min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="pb-2">Consultant</th>
                        <th class="pb-2">Orders</th>
                        <th class="pb-2">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($executive['revenueByAgent'] as $row)
                        <tr>
                            <td class="py-2 font-semibold text-slate-900">{{ $row->name }}</td>
                            <td class="py-2 text-slate-600">{{ $row->orders }}</td>
                            <td class="py-2 text-slate-600">${{ number_format($row->revenue, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="mt-8">
        <livewire:crm.dashboard-stats />
    </div>
</div>
