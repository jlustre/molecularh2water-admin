@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Warranty Registrations</p>
            <h1 class="mt-2 text-3xl font-black tracking-normal text-slate-950">Edit registration</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">Update customer or machine details for this warranty record.</p>
        </section>

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('admin.warranty-registrations.update', $registration) }}">
                @method('PUT')
                @include('admin.warranty-registrations._form', ['submitLabel' => 'Save Changes'])
            </form>
        </section>
    </div>
@endsection
