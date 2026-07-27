@php
    $userId = (int) auth()->id();
    $pipelineMax = max(1, collect($pipeline)->max('count'));
@endphp

<div class="relative p-4 sm:p-6 lg:p-8" data-my-sales-scope>
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
            {{ session('status') }}
        </div>
    @endif

    <section class="relative mb-8 overflow-hidden rounded-3xl border border-teal-200/20 bg-gradient-to-br from-[#041f1e] via-[#062926] to-[#0a3d38] p-6 text-white shadow-xl sm:p-8">
        <div class="pointer-events-none absolute inset-0 opacity-40" aria-hidden="true" style="background:radial-gradient(circle at 85% 15%,rgba(45,212,191,0.28),transparent 40%),radial-gradient(circle at 10% 90%,rgba(16,185,129,0.18),transparent 35%);"></div>
        <div class="relative flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-300/80">My Workspace</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">My Sales</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-teal-100/80">
                    Your personal sales board — deals where you are the credited consultant or the demo partner, with pipeline health and close performance in one place.
                </p>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <span class="rounded-full border border-teal-300/25 bg-white/10 px-3 py-1.5 text-xs font-semibold text-teal-50">
                        {{ $rangeLabel }}
                    </span>
                    <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-semibold text-teal-100/80">
                        {{ number_format($summary['total_sales']) }} deals in range
                    </span>
                </div>
            </div>

            <div class="grid w-full max-w-xl grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-3 backdrop-blur-sm">
                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-teal-200/70">Revenue</p>
                    <p class="mt-1 text-xl font-black">${{ number_format($summary['revenue'], 0) }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 p-3 backdrop-blur-sm">
                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-teal-200/70">Closed</p>
                    <p class="mt-1 text-xl font-black">{{ number_format($summary['completed']) }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 p-3 backdrop-blur-sm">
                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-teal-200/70">Pipeline</p>
                    <p class="mt-1 text-xl font-black">{{ number_format($summary['in_pipeline']) }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 p-3 backdrop-blur-sm">
                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-teal-200/70">Close rate</p>
                    <p class="mt-1 text-xl font-black">{{ $summary['close_rate'] }}%</p>
                </div>
            </div>
        </div>
    </section>

    <div class="mb-6 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm lg:flex-row lg:items-end lg:justify-between">
        <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Date range</label>
                <select class="w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" wire:model.live="datePreset">
                    @foreach ($datePresets as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">From</label>
                <input class="w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" type="date" wire:model.live="dateFrom">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">To</label>
                <input class="w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" type="date" wire:model.live="dateTo">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Credit role</label>
                <select class="w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" wire:model.live="creditFilter">
                    <option value="all">All my credit</option>
                    <option value="consultant">Primary consultant</option>
                    <option value="demo">Demo partner only</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                <input
                    class="w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                    placeholder="Customer, phone, product..."
                    type="search"
                    wire:model.live.debounce.300ms="search"
                >
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button
                type="button"
                wire:click="setViewMode('cards')"
                @class([
                    'rounded-full px-4 py-2 text-sm font-semibold transition',
                    'bg-teal-600 text-white' => $viewMode === 'cards',
                    'bg-slate-100 text-slate-600 hover:bg-slate-200' => $viewMode !== 'cards',
                ])
            >
                Cards
            </button>
            <button
                type="button"
                wire:click="setViewMode('list')"
                @class([
                    'rounded-full px-4 py-2 text-sm font-semibold transition',
                    'bg-teal-600 text-white' => $viewMode === 'list',
                    'bg-slate-100 text-slate-600 hover:bg-slate-200' => $viewMode !== 'list',
                ])
            >
                List
            </button>
        </div>
    </div>

    <div class="mb-8 grid gap-4 lg:grid-cols-12">
        <div class="grid gap-4 sm:grid-cols-2 lg:col-span-7 xl:grid-cols-3">
            @foreach ([
                ['label' => 'Completed revenue', 'value' => '$'.number_format($summary['revenue'], 2), 'hint' => 'Paid / completed deals', 'tone' => 'from-emerald-50 to-white border-emerald-100'],
                ['label' => 'Open pipeline value', 'value' => '$'.number_format($summary['pipeline_value'], 2), 'hint' => 'Deals still in motion', 'tone' => 'from-sky-50 to-white border-sky-100'],
                ['label' => 'Average closed deal', 'value' => '$'.number_format($summary['avg_deal'], 2), 'hint' => 'Across completed sales', 'tone' => 'from-teal-50 to-white border-teal-100'],
                ['label' => 'Primary consultant', 'value' => number_format($summary['as_consultant']), 'hint' => 'You hold primary credit', 'tone' => 'from-violet-50 to-white border-violet-100'],
                ['label' => 'Demo partner credit', 'value' => number_format($summary['as_demo']), 'hint' => 'You assisted another consultant', 'tone' => 'from-amber-50 to-white border-amber-100'],
                ['label' => 'Total deals', 'value' => number_format($summary['total_sales']), 'hint' => 'In the selected range', 'tone' => 'from-slate-50 to-white border-slate-200'],
            ] as $card)
                <div class="rounded-2xl border bg-gradient-to-br p-5 shadow-sm {{ $card['tone'] }}">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">{{ $card['label'] }}</p>
                    <p class="mt-2 text-2xl font-black text-slate-900">{{ $card['value'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $card['hint'] }}</p>
                </div>
            @endforeach
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-5">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-700">Trend</p>
                    <h2 class="mt-1 text-lg font-bold text-slate-900">Completed revenue · 6 months</h2>
                </div>
            </div>
            <div class="flex h-40 items-end gap-3">
                @foreach ($monthlyTrend as $month)
                    <div class="flex min-w-0 flex-1 flex-col items-center justify-end gap-2">
                        <div class="relative flex h-28 w-full items-end justify-center">
                            <div
                                class="w-full max-w-[2.25rem] rounded-t-xl bg-gradient-to-t from-teal-700 to-emerald-400 shadow-sm"
                                style="height: {{ $month['height'] }}%"
                                title="${{ number_format($month['revenue'], 0) }} · {{ $month['count'] }} deals"
                            ></div>
                        </div>
                        <div class="text-center">
                            <p class="text-xs font-bold text-slate-700">{{ $month['label'] }}</p>
                            <p class="text-[10px] text-slate-400">{{ $month['count'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <section class="mb-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-700">Pipeline</p>
                <h2 class="mt-1 text-lg font-bold text-slate-900">Deal stages</h2>
                <p class="mt-1 text-sm text-slate-500">Tap a stage to filter your board.</p>
            </div>
            @if ($statusFilter)
                <button
                    type="button"
                    class="text-sm font-semibold text-teal-700 hover:text-teal-900"
                    wire:click="selectStatus('')"
                >
                    Clear stage filter
                </button>
            @endif
        </div>
        <div class="grid gap-3 md:grid-cols-5">
            @foreach ($pipeline as $stage)
                @php
                    $style = $statusStyles[$stage['status']->value] ?? $statusStyles['application_started'];
                    $active = $statusFilter === $stage['status']->value;
                    $width = (int) max(8, round(($stage['count'] / $pipelineMax) * 100));
                @endphp
                <button
                    type="button"
                    wire:click="selectStatus('{{ $stage['status']->value }}')"
                    @class([
                        'rounded-2xl border p-4 text-left transition hover:-translate-y-0.5 hover:shadow-md',
                        'border-teal-400 ring-2 ring-teal-200 shadow-md' => $active,
                        'border-slate-200 bg-slate-50/60' => ! $active,
                    ])
                >
                    <div class="flex items-center justify-between gap-2">
                        <span @class(['rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide', $style['bg'], $style['text']])>
                            {{ $stage['status']->label() }}
                        </span>
                        <span class="text-lg font-black text-slate-900">{{ $stage['count'] }}</span>
                    </div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200/80">
                        <div class="h-full rounded-full {{ $style['bar'] }}" style="width: {{ $width }}%"></div>
                    </div>
                    <p class="mt-2 text-xs font-semibold text-slate-600">${{ number_format($stage['total'], 0) }}</p>
                </button>
            @endforeach
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Your deals</h2>
                <p class="mt-0.5 text-sm text-slate-500">Personal sales credited to you as consultant or demo partner.</p>
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Per page</span>
                <select class="rounded-lg border-slate-200 py-1 text-sm shadow-sm" wire:model.live="perPage">
                    <option value="6">6</option>
                    <option value="12">12</option>
                    <option value="24">24</option>
                    <option value="48">48</option>
                </select>
            </label>
        </div>

        @if ($viewMode === 'cards')
            <div class="grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-3">
                @forelse ($sales as $sale)
                    @php
                        $style = $statusStyles[$sale->status->value] ?? $statusStyles['application_started'];
                        $isPrimary = (int) $sale->user_id === $userId;
                        $isDemo = (int) $sale->demo_consultant_id === $userId;
                    @endphp
                    <article
                        wire:key="my-sale-card-{{ $sale->id }}"
                        class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-white to-slate-50 shadow-sm transition hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-base font-bold text-slate-900">{{ $sale->displayCustomerName() }}</p>
                                <p class="mt-0.5 truncate text-xs text-slate-500">
                                    {{ $sale->customer_phone ?: ($sale->customer_email ?: 'No contact details') }}
                                </p>
                            </div>
                            <span @class(['shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide', $style['bg'], $style['text']])>
                                {{ $sale->status->label() }}
                            </span>
                        </div>

                        <div class="flex flex-1 flex-col gap-3 px-4 py-4">
                            <div class="flex flex-wrap gap-2">
                                @if ($isPrimary)
                                    <span class="rounded-full bg-teal-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-teal-700">Primary credit</span>
                                @endif
                                @if ($isDemo && ! $isPrimary)
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-700">Demo credit</span>
                                @elseif ($isDemo && $isPrimary)
                                    <span class="rounded-full bg-violet-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-violet-700">Both roles</span>
                                @endif
                            </div>

                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Products</p>
                                @if ($sale->items->isEmpty())
                                    <p class="mt-1 text-sm text-slate-400">No line items</p>
                                @else
                                    <ul class="mt-1 space-y-1">
                                        @foreach ($sale->items->take(3) as $item)
                                            <li class="truncate text-sm text-slate-700">
                                                <span class="font-semibold text-slate-900">{{ $item->quantity }}×</span>
                                                {{ $item->name }}
                                            </li>
                                        @endforeach
                                        @if ($sale->items->count() > 3)
                                            <li class="text-xs font-semibold text-slate-400">+{{ $sale->items->count() - 3 }} more</li>
                                        @endif
                                    </ul>
                                @endif
                            </div>

                            <div class="mt-auto flex items-end justify-between gap-3 border-t border-slate-100 pt-3">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Total</p>
                                    <p class="text-xl font-black text-slate-900">${{ number_format((float) $sale->total, 2) }}</p>
                                </div>
                                <button
                                    type="button"
                                    class="rounded-full bg-teal-600 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white transition hover:bg-teal-700"
                                    wire:click="openSale({{ $sale->id }})"
                                >
                                    View deal
                                </button>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-16 text-center">
                        <p class="text-lg font-bold text-slate-800">No sales in this view yet</p>
                        <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                            When deals are credited to you as the primary consultant or demo partner, they will appear here with status, products, and totals.
                        </p>
                    </div>
                @endforelse
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Customer</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Your role</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Products</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Updated</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($sales as $sale)
                            @php
                                $style = $statusStyles[$sale->status->value] ?? $statusStyles['application_started'];
                                $isPrimary = (int) $sale->user_id === $userId;
                                $isDemo = (int) $sale->demo_consultant_id === $userId;
                            @endphp
                            <tr class="hover:bg-teal-50/40" wire:key="my-sale-row-{{ $sale->id }}">
                                <td class="px-4 py-3">
                                    <p class="text-sm font-semibold text-slate-900">{{ $sale->displayCustomerName() }}</p>
                                    <p class="text-xs text-slate-500">{{ $sale->customer_phone ?: ($sale->customer_email ?: '—') }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    @if ($isPrimary && $isDemo)
                                        Primary + Demo
                                    @elseif ($isPrimary)
                                        Primary consultant
                                    @else
                                        Demo partner
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    @forelse ($sale->items->take(2) as $item)
                                        <div>{{ $item->quantity }}× {{ $item->name }}</div>
                                    @empty
                                        <span class="text-slate-400">—</span>
                                    @endforelse
                                </td>
                                <td class="px-4 py-3">
                                    <span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', $style['bg'], $style['text']])>
                                        {{ $sale->status->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">${{ number_format((float) $sale->total, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-500">{{ $sale->updated_at?->format('M j, Y') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button class="text-sm font-semibold text-teal-700 hover:text-teal-900" type="button" wire:click="openSale({{ $sale->id }})">
                                        View
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-12 text-center text-sm text-slate-500" colspan="7">No sales found for this filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        <div class="border-t border-slate-100 px-5 py-4">
            {{ $sales->links() }}
        </div>
    </section>

    @if ($selectedSale)
        @php
            $style = $statusStyles[$selectedSale->status->value] ?? $statusStyles['application_started'];
            $isPrimary = (int) $selectedSale->user_id === $userId;
            $isDemo = (int) $selectedSale->demo_consultant_id === $userId;
        @endphp
        <div class="fixed inset-0 z-50 flex items-stretch justify-end" role="dialog" aria-modal="true" aria-label="Sale details">
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" wire:click="closeSale"></div>
            <div class="relative flex h-full w-full max-w-lg flex-col overflow-hidden bg-white shadow-2xl">
                <div class="border-b border-slate-100 bg-gradient-to-r from-teal-50 to-white px-5 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-700">Deal detail</p>
                            <h3 class="mt-1 truncate text-xl font-black text-slate-900">{{ $selectedSale->displayCustomerName() }}</h3>
                        </div>
                        <button type="button" class="rounded-full border border-slate-200 px-3 py-1 text-sm font-semibold text-slate-600 hover:bg-slate-50" wire:click="closeSale">
                            Close
                        </button>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span @class(['rounded-full px-2.5 py-1 text-xs font-bold uppercase tracking-wide', $style['bg'], $style['text']])>
                            {{ $selectedSale->status->label() }}
                        </span>
                        @if ($isPrimary)
                            <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-teal-700">Primary</span>
                        @endif
                        @if ($isDemo)
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-amber-700">Demo</span>
                        @endif
                    </div>
                </div>

                <div class="flex-1 space-y-5 overflow-y-auto px-5 py-5">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Total</p>
                            <p class="mt-1 text-2xl font-black text-slate-900">${{ number_format((float) $selectedSale->total, 2) }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Products</p>
                            <p class="mt-1 text-2xl font-black text-slate-900">${{ number_format((float) $selectedSale->subtotal, 2) }}</p>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-bold text-slate-900">Contact</h4>
                        <dl class="mt-2 space-y-2 text-sm">
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">Phone</dt>
                                <dd class="font-medium text-slate-800">{{ $selectedSale->customer_phone ?: '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">Email</dt>
                                <dd class="font-medium text-slate-800">{{ $selectedSale->customer_email ?: '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <h4 class="text-sm font-bold text-slate-900">Credit</h4>
                        <dl class="mt-2 space-y-2 text-sm">
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">Consultant</dt>
                                <dd class="font-medium text-slate-800">{{ $selectedSale->consultant?->name ?: '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">Demo consultant</dt>
                                <dd class="font-medium text-slate-800">{{ $selectedSale->demoConsultant?->name ?: '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <h4 class="text-sm font-bold text-slate-900">Timeline</h4>
                        <ol class="mt-3 space-y-3">
                            @foreach ([
                                'Application' => $selectedSale->application_started_at,
                                'Financing' => $selectedSale->financing_at,
                                'Approved' => $selectedSale->approved_at,
                                'Delivered' => $selectedSale->delivered_at,
                                'Completed' => $selectedSale->completed_at,
                            ] as $label => $stamp)
                                <li class="flex items-center justify-between gap-3 text-sm">
                                    <span class="font-medium text-slate-700">{{ $label }}</span>
                                    <span class="text-slate-500">{{ $stamp?->format('M j, Y g:i A') ?: '—' }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>

                    <div>
                        <h4 class="text-sm font-bold text-slate-900">Line items</h4>
                        <ul class="mt-3 divide-y divide-slate-100 rounded-xl border border-slate-200">
                            @forelse ($selectedSale->items as $item)
                                <li class="flex items-start justify-between gap-3 px-3 py-3 text-sm">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-900">{{ $item->quantity }}× {{ $item->name }}</p>
                                        <p class="text-xs text-slate-500">
                                            {{ $item->item_kind?->value === 'gift' ? 'Gift' : 'Product' }}
                                            @if ($item->sku)
                                                · {{ $item->sku }}
                                            @endif
                                        </p>
                                    </div>
                                    <p class="shrink-0 font-semibold text-slate-800">${{ number_format((float) $item->unit_price * (int) $item->quantity, 2) }}</p>
                                </li>
                            @empty
                                <li class="px-3 py-6 text-center text-sm text-slate-500">No products on this sale.</li>
                            @endforelse
                        </ul>
                    </div>

                    @if (filled($selectedSale->notes))
                        <div>
                            <h4 class="text-sm font-bold text-slate-900">Notes</h4>
                            <p class="mt-2 whitespace-pre-wrap rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-700">{{ $selectedSale->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
