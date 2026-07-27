@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Permissions</p>
            <h1 class="mt-2 text-3xl font-black tracking-normal text-slate-950">Assign roles</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                Choose which roles should include
                <span class="font-semibold text-slate-800">{{ $permission['label'] }}</span>
                (<span class="font-mono text-teal-700">({{ $permission['key'] }})</span>.
            </p>
            <p class="mt-3 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                {{ $permission['category_label'] }}
            </p>
        </section>

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('admin.permissions.update', $permission['key']) }}">
                @csrf
                @method('PUT')

                <div class="space-y-3">
                    @php
                        $checkedRoleIds = collect(old('role_ids', $selectedRoleIds))
                            ->map(fn ($id) => (int) $id)
                            ->all();
                    @endphp
                    @forelse ($roles as $role)
                        <label class="flex items-center gap-3 rounded-md border border-slate-100 px-4 py-3 text-sm transition hover:bg-teal-50">
                            <input
                                type="checkbox"
                                name="role_ids[]"
                                value="{{ $role->id }}"
                                @checked(in_array($role->id, $checkedRoleIds, true))
                                class="rounded border-teal-200 text-teal-600 focus:ring-teal-500"
                            >
                            <span class="min-w-0">
                                <span class="block font-semibold text-slate-900">{{ $role->name }}</span>
                                <span class="block text-xs text-slate-500">{{ $role->slug }} · {{ ucfirst($role->status) }}</span>
                            </span>
                        </label>
                    @empty
                        <p class="rounded-md border border-dashed border-teal-200 bg-teal-50 px-4 py-6 text-center text-sm font-semibold text-teal-800">
                            No roles available.
                        </p>
                    @endforelse
                </div>
                @error('role_ids')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                @error('role_ids.*')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <a href="{{ route('admin.permissions.index') }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-5 py-2.5 text-sm font-bold text-teal-800 shadow-sm transition hover:bg-teal-50">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-2.5 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
                        Save Role Assignments
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection
