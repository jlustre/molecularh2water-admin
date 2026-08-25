@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Issue Reports</p>
            <h1 class="mt-2 text-3xl font-black tracking-normal text-slate-950">Log Issue</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                Super-admins can file a report on behalf of a user. A confirmation email is sent to the reporter.
            </p>
        </section>

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('admin.issue-reports.store') }}" enctype="multipart/form-data">
                @include('admin.issue-reports._form', ['submitLabel' => 'Create Issue Report'])
            </form>
        </section>
    </div>
@endsection
