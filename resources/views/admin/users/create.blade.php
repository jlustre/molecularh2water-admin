@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">User Directory</p>
            <h1 class="mt-2 text-3xl font-black tracking-normal text-slate-950">Add user</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">Create a new account and choose whether the email should start as verified.</p>
        </section>

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @include('admin.users._form', ['submitLabel' => 'Create User'])
            </form>
        </section>
    </div>
@endsection
