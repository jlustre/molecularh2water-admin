@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        @if (session('status'))
            <div class="rounded-md border border-teal-100 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
                {{ session('status') }}
            </div>
        @endif

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Warranty Registration</p>
                    <h1 class="mt-2 text-3xl font-black tracking-normal text-slate-950">{{ $registration->customer_name }}</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Registered {{ $registration->created_at?->format('M j, Y g:i A') }}.</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.warranty-registrations.edit', $registration) }}" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-2.5 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
                        Edit Registration
                    </a>
                    <a href="{{ route('admin.warranty-registrations.index') }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-5 py-2.5 text-sm font-bold text-teal-800 transition hover:bg-teal-50">
                        Back To List
                    </a>
                </div>
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Customer Details</h2>
                <dl class="mt-5 space-y-4 text-sm">
                    <div>
                        <dt class="font-semibold text-slate-500">Name</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $registration->customer_name }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Email</dt>
                        <dd class="mt-1 text-slate-900">{{ $registration->email }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Phone</dt>
                        <dd class="mt-1 text-slate-900">{{ $registration->phone }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Machine Details</h2>
                <dl class="mt-5 space-y-4 text-sm">
                    <div>
                        <dt class="font-semibold text-slate-500">Serial Number</dt>
                        <dd class="mt-1 font-mono font-bold text-slate-900">{{ $registration->serial_number }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Machine Model</dt>
                        <dd class="mt-1 text-slate-900">{{ $registration->machine_model }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Purchase Date</dt>
                        <dd class="mt-1 text-slate-900">{{ $registration->purchase_date?->format('M j, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Purchased From</dt>
                        <dd class="mt-1 text-slate-900">{{ $registration->purchased_from ?: 'Not provided' }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        @if ($registration->notes)
            <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Notes</h2>
                <p class="mt-3 whitespace-pre-wrap text-sm leading-7 text-slate-700">{{ $registration->notes }}</p>
            </section>
        @endif

        <section class="rounded-lg border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">
            Public registration page: <a href="{{ $warrantyUrl }}" target="_blank" rel="noreferrer" class="font-semibold text-teal-700 hover:text-teal-800">{{ $warrantyUrl }}</a>
        </section>
    </div>
@endsection
