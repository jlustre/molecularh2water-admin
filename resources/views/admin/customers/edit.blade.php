@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Customers Management</p>
            <h1 class="mt-2 text-3xl font-black tracking-normal text-slate-950">Edit customer</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                Update the single CRM record for {{ $customer->fullName() }}.
            </p>
        </section>

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('admin.customers.update', $customer) }}">
                @method('PUT')
                @include('admin.customers._form', ['submitLabel' => 'Save Changes'])
            </form>
        </section>
    </div>
@endsection
