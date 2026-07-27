@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Installer Management</p>
            <h1 class="mt-2 text-3xl font-black tracking-normal text-slate-950">Add installer</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                Installers do not need an app account. Store contact details for scheduling and history tracking.
            </p>
        </section>

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('admin.installers.store') }}">
                @include('admin.installers._form', [
                    'submitLabel' => 'Create Installer',
                    'cancelRoute' => route('admin.installers.index'),
                ])
            </form>
        </section>
    </div>
@endsection
