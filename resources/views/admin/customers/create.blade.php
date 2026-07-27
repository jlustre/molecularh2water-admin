@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Customers Management</p>
            <h1 class="mt-2 text-3xl font-black tracking-normal text-slate-950">Add customer</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                Enter the person once into the CRM customers table. Installer jobs and product history read from this same record.
            </p>
        </section>

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('admin.customers.store') }}">
                @include('admin.customers._form', ['submitLabel' => 'Create Customer'])
            </form>
        </section>
    </div>
@endsection
