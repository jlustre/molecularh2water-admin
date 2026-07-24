@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Email Mappings</p>
            <h1 class="mt-2 text-3xl font-black tracking-normal text-slate-950">Edit mapping</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                Update recipients for this form. Use + to add more emails, or remove addresses you no longer want notified.
            </p>
        </section>

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('admin.email-mappings.update', $mapping) }}">
                @method('PUT')
                @include('admin.email-mappings._form', [
                    'submitLabel' => 'Save Changes',
                    'allowMultipleEmails' => true,
                    'recipientEmails' => $recipientEmails,
                ])
            </form>
        </section>
    </div>
@endsection
