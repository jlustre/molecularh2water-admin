@extends('layouts.admin')

@section('content')
    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-md border border-teal-100 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
                {{ session('status') }}
            </div>
        @endif

        <section class="overflow-hidden rounded-lg border border-teal-100 bg-gradient-to-br from-[#041f1e] via-[#062926] to-[#031a19] text-white shadow-lg">
            <div class="relative px-6 py-7 sm:px-8">
                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-3xl">
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-100">Customers Management</p>
                        <h1 class="mt-3 text-3xl font-black tracking-normal sm:text-4xl">One customer record. Consultant and products come from the order.</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-teal-50/75">
                            This list reads the CRM customers table — the same people moved from leads/prospects after purchase. No duplicate directory copy.
                        </p>
                    </div>

                    @if (auth()->user()?->hasPermission('customer-directory.manage'))
                        <a href="{{ route('admin.customers.create') }}" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-3 text-sm font-bold text-[#031a19] transition hover:bg-teal-300">
                            Add Customer
                        </a>
                    @endif
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-3">
            @foreach ([
                ['label' => 'Total', 'value' => $totalCount, 'meta' => 'CRM customers'],
                ['label' => 'Customer (C)', 'value' => $customerTypeCount, 'meta' => 'Purchased only'],
                ['label' => 'Both (B)', 'value' => $bothTypeCount, 'meta' => 'Customer & recruit'],
            ] as $card)
                <div class="rounded-lg border border-teal-100 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold text-slate-500">{{ $card['meta'] }}</p>
                    <div class="mt-2 flex items-end justify-between gap-2">
                        <p class="text-2xl font-black text-slate-950">{{ $card['value'] }}</p>
                        <span class="rounded-md bg-teal-50 px-2 py-1 text-[0.65rem] font-bold uppercase tracking-wide text-teal-700">{{ $card['label'] }}</span>
                    </div>
                </div>
            @endforeach
        </section>

        <section class="rounded-lg border border-teal-100 bg-white p-4 shadow-sm sm:p-6">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-700">CRM customers</p>
                    <h2 class="mt-1 text-xl font-black text-slate-950">Customer list</h2>
                </div>

                <form action="{{ route('admin.customers.index') }}" class="flex flex-wrap gap-2" method="GET">
                    <input class="rounded-full border border-teal-100 px-3.5 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400" name="search" placeholder="Search name, consultant, order..." type="search" value="{{ request('search') }}">
                    <select class="rounded-full border border-teal-100 px-3.5 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400" name="engagement_type">
                        <option value="">Any type</option>
                        @foreach ($engagementTypes as $value => $label)
                            <option @selected(request('engagement_type') === $value) value="{{ $value }}">{{ $value }} — {{ $label }}</option>
                        @endforeach
                    </select>
                    <select class="rounded-full border border-teal-100 px-3.5 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400" name="state">
                        <option value="">Any state</option>
                        @foreach ($states as $value => $label)
                            <option @selected(request('state') === $value) value="{{ $value }}">{{ $value }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-full bg-teal-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-teal-700" type="submit">Filter</button>
                </form>
            </div>

            <div class="mt-5 overflow-x-auto rounded-lg border border-slate-100">
                <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-2.5">Customer</th>
                            <th class="px-3 py-2.5">Consultant</th>
                            <th class="px-3 py-2.5">Products</th>
                            <th class="px-3 py-2.5">Order</th>
                            <th class="px-3 py-2.5">Phone</th>
                            <th class="px-3 py-2.5">Email</th>
                            <th class="px-3 py-2.5">Location</th>
                            <th class="px-3 py-2.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                        @forelse ($customers as $customer)
                            @php $latestOrder = $customer->latestOrder(); @endphp
                            <tr class="transition hover:bg-teal-50/50">
                                <td class="px-3 py-3 font-semibold text-slate-900">
                                    {{ $customer->fullName() }}
                                    @if ($customer->engagement_type)
                                        <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide text-slate-600">{{ $customer->engagement_type->value }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">{{ $customer->assignedUser?->name ?: '—' }}</td>
                                <td class="px-3 py-3 max-w-xs">{{ $customer->productsSummaryLabel() }}</td>
                                <td class="px-3 py-3">{{ $latestOrder?->order_number ?: '—' }}</td>
                                <td class="px-3 py-3">{{ $customer->phone ?: '—' }}</td>
                                <td class="px-3 py-3">{{ $customer->email ?: '—' }}</td>
                                <td class="px-3 py-3">{{ $customer->locationSummary() ?: '—' }}</td>
                                <td class="px-3 py-3">
                                    <div class="flex justify-end gap-2">
                                        @if (auth()->user()?->hasPermission('customer-directory.manage'))
                                            <a href="{{ route('admin.customers.edit', $customer) }}" class="inline-flex size-9 items-center justify-center rounded-md border border-teal-100 bg-white text-teal-700 transition hover:bg-teal-50" title="Edit">
                                                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4 20 4.5-1 10-10a2.12 2.12 0 0 0-3-3l-10 10L4 20Z"/><path stroke-linecap="round" stroke-linejoin="round" d="m14 7 3 3"/></svg>
                                            </a>
                                            <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}" onsubmit="return confirm('Remove this customer from the active list?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex size-9 items-center justify-center rounded-md border border-red-100 bg-white text-red-600 transition hover:bg-red-50" title="Remove">
                                                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 7h14M10 11v6M14 11v6M8 7l1-3h6l1 3M7 7l1 13h8l1-13"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-3 py-12 text-center">
                                    <p class="text-base font-bold text-slate-900">No customers found</p>
                                    <p class="mt-1 text-sm text-slate-500">Customers appear here after purchase, or when added manually once.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $customers->links() }}
            </div>
        </section>
    </div>
@endsection
