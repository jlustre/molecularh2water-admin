@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Email Mappings</p>
            <h1 class="mt-2 text-3xl font-black tracking-normal text-slate-950">Add mapping</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                Choose a form and one or more recipient emails to notify when that form is submitted from the website.
            </p>
        </section>

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('admin.email-mappings.store') }}">
                @include('admin.email-mappings._form', [
                    'submitLabel' => 'Create Mapping',
                    'allowMultipleEmails' => true,
                ])
            </form>
        </section>
    </div>
@endsection
