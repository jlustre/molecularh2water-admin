<div class="p-4 sm:p-6 lg:p-8">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-600">CRM</p>
            <h1 class="mt-1 text-3xl font-bold text-slate-900">Sales</h1>
            <p class="mt-1 text-sm text-slate-500">Orders, quotations, and revenue across your accessible contacts.</p>
        </div>
        <select class="rounded-xl border-slate-200 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" wire:model.live="period">
            <option value="7d">Last 7 days</option>
            <option value="30d">Last 30 days</option>
            <option value="90d">Last 90 days</option>
            <option value="all">All time</option>
        </select>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ([
            ['label' => 'Orders', 'value' => number_format($summary['orders'] ?? 0), 'hint' => 'Created in period'],
            ['label' => 'Quotations', 'value' => number_format($summary['quotations'] ?? 0), 'hint' => 'Created in period'],
            ['label' => 'Open Quotes', 'value' => number_format($summary['open_quotations'] ?? 0), 'hint' => 'Draft / presented / viewed'],
            ['label' => 'Pending Payments', 'value' => number_format($summary['pending_payments'] ?? 0), 'hint' => 'Awaiting full payment'],
            ['label' => 'Revenue', 'value' => '$'.number_format($summary['revenue'] ?? 0, 2), 'hint' => 'Paid orders'],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ $card['value'] }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $card['hint'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-2">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-lg font-bold text-slate-900">Recent Orders</h2>
                <p class="mt-0.5 text-sm text-slate-500">Latest orders for contacts you can access.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Order</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Contact</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentOrders as $order)
                            @php
                                $contactUrl = $this->contactUrl($order->contact);
                            @endphp
                            <tr class="hover:bg-teal-50/40">
                                <td class="px-4 py-3">
                                    <p class="text-sm font-semibold text-slate-900">{{ $order->order_number }}</p>
                                    <p class="text-xs text-slate-400">{{ $order->created_at?->format('M j, Y') }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($contactUrl)
                                        <a class="text-sm font-medium text-teal-700 hover:text-teal-800 hover:underline" href="{{ $contactUrl }}">
                                            {{ $this->contactLabel($order->contact) }}
                                        </a>
                                    @else
                                        <span class="text-sm text-slate-600">{{ $this->contactLabel($order->contact) }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col gap-1">
                                        <span class="inline-flex w-fit rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
                                            {{ $order->status?->label() ?? $order->status }}
                                        </span>
                                        <span @class([
                                            'inline-flex w-fit rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                            'bg-emerald-50 text-emerald-700' => $order->payment_status === \App\Enums\Crm\PaymentStatus::Paid,
                                            'bg-amber-50 text-amber-700' => in_array($order->payment_status, [\App\Enums\Crm\PaymentStatus::Pending, \App\Enums\Crm\PaymentStatus::Partial], true),
                                            'bg-slate-100 text-slate-600' => $order->payment_status === \App\Enums\Crm\PaymentStatus::Refunded,
                                        ])>
                                            {{ $order->payment_status?->label() ?? $order->payment_status }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-slate-900">
                                    ${{ number_format((float) $order->total, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-10 text-center text-sm text-slate-500" colspan="4">
                                    No orders yet. Create quotations and convert them from a contact profile.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-lg font-bold text-slate-900">Recent Quotations</h2>
                <p class="mt-0.5 text-sm text-slate-500">Latest quotes ready for follow-up or conversion.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Quote</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Contact</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentQuotations as $quotation)
                            @php
                                $contactUrl = $this->contactUrl($quotation->contact);
                            @endphp
                            <tr class="hover:bg-teal-50/40">
                                <td class="px-4 py-3">
                                    <p class="text-sm font-semibold text-slate-900">{{ $quotation->quote_number }}</p>
                                    <p class="text-xs text-slate-400">{{ $quotation->created_at?->format('M j, Y') }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($contactUrl)
                                        <a class="text-sm font-medium text-teal-700 hover:text-teal-800 hover:underline" href="{{ $contactUrl }}">
                                            {{ $this->contactLabel($quotation->contact) }}
                                        </a>
                                    @else
                                        <span class="text-sm text-slate-600">{{ $this->contactLabel($quotation->contact) }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                        'bg-slate-100 text-slate-700' => $quotation->status === \App\Enums\Crm\QuotationStatus::Draft,
                                        'bg-teal-50 text-teal-700' => in_array($quotation->status, [\App\Enums\Crm\QuotationStatus::Presented, \App\Enums\Crm\QuotationStatus::Viewed], true),
                                        'bg-emerald-50 text-emerald-700' => $quotation->status === \App\Enums\Crm\QuotationStatus::Accepted,
                                        'bg-rose-50 text-rose-700' => in_array($quotation->status, [\App\Enums\Crm\QuotationStatus::Declined, \App\Enums\Crm\QuotationStatus::Expired], true),
                                    ])>
                                        {{ $quotation->status?->label() ?? $quotation->status }}
                                    </span>
                                    @if ($quotation->order)
                                        <p class="mt-1 text-xs text-slate-400">Order {{ $quotation->order->order_number }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-slate-900">
                                    ${{ number_format((float) $quotation->total, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-10 text-center text-sm text-slate-500" colspan="4">
                                    No quotations yet. Add quotes from a prospect or customer profile.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
