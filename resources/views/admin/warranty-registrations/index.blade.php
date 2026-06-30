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
                <div class="absolute inset-0 opacity-[0.08] [background-image:linear-gradient(rgba(45,212,191,.85)_1px,transparent_1px),linear-gradient(90deg,rgba(45,212,191,.85)_1px,transparent_1px)] [background-size:36px_36px]"></div>
                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-3xl">
                        <p class="inline-flex items-center gap-2 rounded-full border border-teal-300/25 bg-white/[0.07] px-3 py-1 text-xs font-bold uppercase tracking-[0.22em] text-teal-100">
                            <span class="size-2 rounded-full bg-teal-300 shadow-[0_0_14px_rgba(45,212,191,0.9)]"></span>
                            Warranty Registrations
                        </p>
                        <h1 class="mt-5 text-3xl font-black tracking-normal sm:text-4xl">Manage machine warranty registrations and QR access.</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-teal-50/[0.72]">
                            Review customer submissions, update registration details, and download the environment-specific QR code for packaging and manuals.
                        </p>
                    </div>

                    <a href="{{ $warrantyUrl }}" target="_blank" rel="noreferrer" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-3 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
                        Open Public Page
                    </a>
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[320px_1fr]">
            <div class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Warranty QR Code</p>
                        <h2 class="mt-2 text-xl font-black text-slate-950">Scan to register</h2>
                    </div>
                    <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.14em] text-teal-700">
                        {{ $environmentLabel }}
                    </span>
                </div>

                <p class="mt-3 text-sm leading-6 text-slate-500">
                    This QR code points to the customer registration page for the current environment.
                </p>

                <div class="mt-5 flex justify-center rounded-lg border border-slate-100 bg-slate-50 p-5">
                    <canvas id="warranty-qr-canvas" width="220" height="220" aria-label="Warranty registration QR code"></canvas>
                </div>

                <p class="mt-4 break-all text-center text-sm font-semibold text-slate-700">{{ $warrantyUrl }}</p>

                <div class="mt-5 flex flex-col gap-3">
                    <button type="button" id="download-warranty-qr" class="inline-flex items-center justify-center rounded-md bg-teal-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-teal-700">
                        Download QR Image
                    </button>
                    <a href="{{ $warrantyUrl }}" target="_blank" rel="noreferrer" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-4 py-2.5 text-sm font-bold text-teal-800 transition hover:bg-teal-50">
                        Copy Link Target
                    </a>
                </div>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                @foreach ([
                    ['label' => 'Total', 'value' => $totalRegistrations, 'meta' => 'All registrations'],
                    ['label' => 'This Month', 'value' => $thisMonthRegistrations, 'meta' => 'Current month'],
                    ['label' => 'Last 30 Days', 'value' => $newRegistrations, 'meta' => 'Recent activity'],
                    ['label' => 'Machine Models', 'value' => $uniqueModels, 'meta' => 'Unique models'],
                ] as $card)
                    <div class="rounded-lg border border-teal-100 bg-white p-5 shadow-sm">
                        <p class="text-sm font-semibold text-slate-500">{{ $card['meta'] }}</p>
                        <div class="mt-3 flex items-end justify-between gap-4">
                            <h2 class="text-3xl font-black text-slate-950">{{ $card['value'] }}</h2>
                            <span class="rounded-md bg-teal-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.16em] text-teal-700">{{ $card['label'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Registration Records</p>
                    <h2 class="mt-2 text-2xl font-black tracking-normal text-slate-950">Customer warranty submissions</h2>
                </div>

                <form method="GET" action="{{ route('admin.warranty-registrations.index') }}" class="grid gap-2 md:grid-cols-2 xl:flex">
                    <input name="search" type="search" value="{{ request('search') }}" class="rounded-full border border-teal-100 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-teal-400 focus:ring-teal-400 xl:w-72" placeholder="Search customer, serial, email...">
                    <select name="machine_model" class="rounded-full border border-teal-100 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400">
                        <option value="">All models</option>
                        @foreach ($machineModels as $machineModel)
                            <option value="{{ $machineModel }}" @selected(request('machine_model') === $machineModel)>{{ $machineModel }}</option>
                        @endforeach
                    </select>
                    <select name="registered" class="rounded-full border border-teal-100 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400">
                        <option value="">Any registered date</option>
                        <option value="7_days" @selected(request('registered') === '7_days')>Last 7 days</option>
                        <option value="30_days" @selected(request('registered') === '30_days')>Last 30 days</option>
                        <option value="90_days" @selected(request('registered') === '90_days')>Last 90 days</option>
                    </select>
                    <select name="per_page" class="rounded-full border border-teal-100 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400">
                        @foreach ([10, 25, 50] as $size)
                            <option value="{{ $size }}" @selected((int) request('per_page', 10) === $size)>{{ $size }} / page</option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-full bg-teal-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-teal-700">Filter</button>
                </form>
            </div>

            <div class="mt-6 overflow-hidden rounded-lg border border-slate-100">
                <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Serial Number</th>
                            <th class="px-4 py-3">Model</th>
                            <th class="px-4 py-3">Purchase Date</th>
                            <th class="px-4 py-3">Registered</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                        @forelse ($registrations as $registration)
                            <tr class="transition hover:bg-teal-50/50">
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-slate-900">{{ $registration->customer_name }}</p>
                                    <p class="truncate text-xs font-medium text-teal-700">{{ $registration->email }}</p>
                                    <p class="text-xs text-slate-500">{{ $registration->phone }}</p>
                                </td>
                                <td class="px-4 py-4 font-mono text-xs font-bold text-slate-800">{{ $registration->serial_number }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $registration->machine_model }}</td>
                                <td class="px-4 py-4 text-slate-500">{{ $registration->purchase_date?->format('M j, Y') }}</td>
                                <td class="px-4 py-4 text-slate-500">{{ $registration->created_at?->format('M j, Y') }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <a href="{{ route('admin.warranty-registrations.show', $registration) }}" aria-label="View" title="View" class="group relative inline-flex size-9 items-center justify-center rounded-md border border-teal-100 bg-white text-teal-700 transition hover:bg-teal-50 hover:text-teal-800">
                                            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5S21.75 12 21.75 12s-3.75 7.5-9.75 7.5S2.25 12 2.25 12Z"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>
                                            <span class="sr-only">View</span>
                                        </a>
                                        <a href="{{ route('admin.warranty-registrations.edit', $registration) }}" aria-label="Edit" title="Edit" class="group relative inline-flex size-9 items-center justify-center rounded-md border border-teal-100 bg-white text-teal-700 transition hover:bg-teal-50 hover:text-teal-800">
                                            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4 20 4.5-1 10-10a2.12 2.12 0 0 0-3-3l-10 10L4 20Z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14 7 3 3"/>
                                            </svg>
                                            <span class="sr-only">Edit</span>
                                        </a>
                                        <form method="POST" action="{{ route('admin.warranty-registrations.destroy', $registration) }}" onsubmit="return confirm('Delete this warranty registration?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" aria-label="Delete" title="Delete" class="inline-flex size-9 items-center justify-center rounded-md border border-red-100 bg-white text-red-600 transition hover:bg-red-50 hover:text-red-700">
                                                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 7h14M10 11v6M14 11v6M8 7l1-3h6l1 3M7 7l1 13h8l1-13"/>
                                                </svg>
                                                <span class="sr-only">Delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <p class="text-base font-bold text-slate-900">No warranty registrations yet</p>
                                    <p class="mt-1 text-sm text-slate-500">Customer submissions from the public warranty page will appear here.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $registrations->links() }}
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.4/build/qrcode.min.js"></script>
    <script>
        (function () {
            const warrantyUrl = @json($warrantyUrl);
            const canvas = document.getElementById('warranty-qr-canvas');
            const downloadButton = document.getElementById('download-warranty-qr');

            if (!canvas || !window.QRCode) {
                return;
            }

            window.QRCode.toCanvas(canvas, warrantyUrl, {
                width: 220,
                margin: 2,
                color: {
                    dark: '#073B4C',
                    light: '#FFFFFF',
                },
            });

            downloadButton?.addEventListener('click', function () {
                const link = document.createElement('a');
                link.download = 'warranty-registration-qr.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        })();
    </script>
@endsection
