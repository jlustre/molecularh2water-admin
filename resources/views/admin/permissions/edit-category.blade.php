@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-2xl space-y-6">
        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Permissions</p>
            <h1 class="mt-2 text-3xl font-black tracking-normal text-slate-950">Edit category</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                Update the category label{{ $category->is_system ? '. System category keys cannot be changed.' : ' and key.' }}
            </p>
            @if ($category->is_system)
                <p class="mt-3 inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">System category</p>
            @endif
        </section>

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('admin.permission-catalog.categories.update', $category) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="key" class="block text-sm font-semibold text-slate-700">Key</label>
                    <input
                        id="key"
                        name="key"
                        type="text"
                        value="{{ old('key', $category->key) }}"
                        @disabled($category->is_system)
                        @required(! $category->is_system)
                        class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500 disabled:bg-slate-50 disabled:text-slate-500"
                        pattern="[a-z][a-z0-9_]*"
                        placeholder="custom_tools"
                    >
                    @error('key')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="label" class="block text-sm font-semibold text-slate-700">Label</label>
                    <input
                        id="label"
                        name="label"
                        type="text"
                        value="{{ old('label', $category->label) }}"
                        required
                        class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        placeholder="Custom Tools"
                    >
                    @error('label')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <a href="{{ route('admin.permissions.index') }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-5 py-2.5 text-sm font-bold text-teal-800 shadow-sm transition hover:bg-teal-50">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-2.5 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
                        Save Category
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection
