@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Blog / Education</p>
            <h1 class="mt-2 text-3xl font-black tracking-normal text-slate-950">Edit Post</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                Update the title, body, status, publish date, or display order.
            </p>
        </section>

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('admin.blog.update', $post) }}">
                @method('PUT')
                @include('admin.blog-posts._form', ['submitLabel' => 'Save Changes'])
            </form>
        </section>
    </div>
@endsection
