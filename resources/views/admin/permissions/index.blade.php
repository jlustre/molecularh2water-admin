@extends('layouts.admin')

@php
    $colorClasses = [
        'teal' => 'bg-teal-50 text-teal-700',
        'emerald' => 'bg-emerald-50 text-emerald-700',
        'cyan' => 'bg-cyan-50 text-cyan-700',
        'blue' => 'bg-blue-50 text-blue-700',
        'indigo' => 'bg-indigo-50 text-indigo-700',
        'amber' => 'bg-amber-50 text-amber-700',
        'rose' => 'bg-rose-50 text-rose-700',
        'slate' => 'bg-slate-100 text-slate-700',
    ];
@endphp

@section('content')
    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-md border border-teal-100 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-md border border-rose-100 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="overflow-hidden rounded-lg border border-teal-100 bg-gradient-to-br from-[#041f1e] via-[#062926] to-[#031a19] text-white shadow-lg">
            <div class="relative px-6 py-7 sm:px-8">
                <div class="absolute inset-0 opacity-[0.08] [background-image:linear-gradient(rgba(45,212,191,.85)_1px,transparent_1px),linear-gradient(90deg,rgba(45,212,191,.85)_1px,transparent_1px)] [background-size:36px_36px]"></div>
                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-3xl">
                        <p class="inline-flex items-center gap-2 rounded-full border border-teal-300/25 bg-white/[0.07] px-3 py-1 text-xs font-bold uppercase tracking-[0.22em] text-teal-100">
                            <span class="size-2 rounded-full bg-teal-300 shadow-[0_0_14px_rgba(45,212,191,0.9)]"></span>
                            Permissions
                        </p>
                        <h1 class="mt-5 text-3xl font-black tracking-normal sm:text-4xl">Browse and assign capability keys across roles.</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-teal-50/[0.72]">
                            Search the permission catalog, add categories or keys, and control which roles receive each capability.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        @if (auth()->user()?->hasPermission('roles.view'))
                            <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center justify-center rounded-md border border-teal-200/30 bg-white/[0.08] px-5 py-3 text-sm font-bold text-white transition hover:bg-white/[0.12]">
                                Manage Roles
                            </a>
                        @endif
                        @if (auth()->user()?->isSuperAdmin() && auth()->user()?->hasPermission('permissions.export'))
                            <form method="POST" action="{{ route('admin.permissions.update-seeder') }}">
                                @csrf
                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-md border border-teal-200/30 bg-white/[0.08] px-5 py-3 text-sm font-bold text-white transition hover:bg-white/[0.12]" title="Developer tool: write current role permission assignments into RolesSeeder.php">
                                    Update Seeder
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Permissions', 'value' => $totalPermissions, 'meta' => 'Catalog keys'],
                ['label' => 'Categories', 'value' => $categoryCount, 'meta' => 'Feature groups'],
                ['label' => 'Assigned', 'value' => $assignedPermissions, 'meta' => 'On at least one role'],
                ['label' => 'Unassigned', 'value' => $unassignedPermissions, 'meta' => 'Not used yet'],
            ] as $card)
                <div class="rounded-lg border border-teal-100 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold text-slate-500">{{ $card['meta'] }}</p>
                    <div class="mt-3 flex items-end justify-between gap-4">
                        <h2 class="text-3xl font-black text-slate-950">{{ $card['value'] }}</h2>
                        <span class="rounded-md bg-teal-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.16em] text-teal-700">{{ $card['label'] }}</span>
                    </div>
                </div>
            @endforeach
        </section>

        @if (auth()->user()?->hasPermission('permissions.manage'))
            <section class="grid gap-5 lg:grid-cols-2">
                <div class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Catalog</p>
                    <h2 class="mt-2 text-xl font-black tracking-normal text-slate-950">Add category</h2>
                    <form method="POST" action="{{ route('admin.permission-catalog.categories.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                        @csrf
                        <div>
                            <label for="category_key" class="block text-sm font-semibold text-slate-700">Key</label>
                            <input id="category_key" name="key" type="text" value="{{ old('key') }}" required pattern="[a-z][a-z0-9_]*" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" placeholder="custom_tools">
                        </div>
                        <div>
                            <label for="category_label" class="block text-sm font-semibold text-slate-700">Label</label>
                            <input id="category_label" name="label" type="text" value="{{ old('label') }}" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" placeholder="Custom Tools">
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit" class="inline-flex items-center justify-center rounded-md bg-teal-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-teal-700">
                                Add Category
                            </button>
                        </div>
                    </form>

                    @if ($categoryModels->isNotEmpty())
                        <div class="mt-6 space-y-2">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Categories</p>
                            @foreach ($categoryModels as $category)
                                <div class="flex items-center justify-between gap-3 rounded-md border border-slate-100 px-3 py-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $category->label }}</p>
                                        <p class="font-mono text-xs text-teal-700">{{ $category->key }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if ($category->is_system)
                                            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700">System</span>
                                        @endif
                                        <a href="{{ route('admin.permission-catalog.categories.edit', $category) }}" class="text-xs font-bold text-teal-700 hover:text-teal-900">Edit</a>
                                        @unless ($category->is_system)
                                            <form method="POST" action="{{ route('admin.permission-catalog.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category and its custom permissions?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-bold text-rose-600 hover:text-rose-800">Delete</button>
                                            </form>
                                        @endunless
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Catalog</p>
                    <h2 class="mt-2 text-xl font-black tracking-normal text-slate-950">Add permission</h2>
                    <form method="POST" action="{{ route('admin.permission-catalog.permissions.store') }}" class="mt-4 grid gap-3">
                        @csrf
                        <div>
                            <label for="permission_category_id" class="block text-sm font-semibold text-slate-700">Category</label>
                            <select id="permission_category_id" name="permission_category_id" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                <option value="">Select category</option>
                                @foreach ($categoryModels as $category)
                                    <option value="{{ $category->id }}" @selected((string) old('permission_category_id') === (string) $category->id)>{{ $category->label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label for="permission_key" class="block text-sm font-semibold text-slate-700">Key</label>
                                <input id="permission_key" name="key" type="text" value="{{ old('key') }}" required pattern="[a-z][a-z0-9_.-]*" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" placeholder="custom.tools.view">
                            </div>
                            <div>
                                <label for="permission_label" class="block text-sm font-semibold text-slate-700">Label</label>
                                <input id="permission_label" name="label" type="text" value="{{ old('label') }}" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" placeholder="View custom tools">
                            </div>
                        </div>
                        <div>
                            <button type="submit" class="inline-flex items-center justify-center rounded-md bg-teal-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-teal-700">
                                Add Permission
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        @endif

        <section class="rounded-lg border border-teal-100 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Capability Catalog</p>
                    <h2 class="mt-2 text-2xl font-black tracking-normal text-slate-950">Permissions library</h2>
                </div>

                <form method="GET" action="{{ route('admin.permissions.index') }}" class="grid gap-2 md:grid-cols-2 xl:flex xl:flex-wrap">
                    <input name="search" type="search" value="{{ request('search') }}" class="rounded-full border border-teal-100 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-teal-400 focus:ring-teal-400 xl:w-64" placeholder="Search permissions...">
                    <select name="category" class="rounded-full border border-teal-100 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400">
                        <option value="">All categories</option>
                        @foreach ($categories as $value => $label)
                            <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="role" class="rounded-full border border-teal-100 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400">
                        <option value="">Any role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected((string) request('role') === (string) $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                    <select name="assignment" class="rounded-full border border-teal-100 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400">
                        <option value="">Any assignment</option>
                        <option value="assigned" @selected(request('assignment') === 'assigned')>Assigned</option>
                        <option value="unassigned" @selected(request('assignment') === 'unassigned')>Unassigned</option>
                    </select>
                    <select name="per_page" class="rounded-full border border-teal-100 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:ring-teal-400">
                        @foreach ([10, 15, 25, 50] as $size)
                            <option value="{{ $size }}" @selected((int) request('per_page', 15) === $size)>{{ $size }} / page</option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-full bg-teal-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-teal-700">Filter</button>
                    @if (request()->hasAny(['search', 'category', 'role', 'assignment']))
                        <a href="{{ route('admin.permissions.index') }}" class="inline-flex items-center justify-center rounded-full border border-teal-200 bg-white px-5 py-2.5 text-sm font-bold text-teal-800 transition hover:bg-teal-50">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            <div class="mt-6 overflow-hidden rounded-lg border border-slate-100">
                <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Permission</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Roles</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                        @forelse ($permissions as $permission)
                            <tr class="transition hover:bg-teal-50/50">
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-slate-900">{{ $permission['label'] }}</p>
                                        @if (! empty($permission['is_system']))
                                            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700">System</span>
                                        @endif
                                    </div>
                                    <p class="mt-1 font-mono text-xs text-teal-700">{{ $permission['key'] }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">
                                        {{ $permission['category_label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    @if ($permission['roles']->isEmpty())
                                        <span class="text-xs font-semibold text-slate-400">No roles</span>
                                    @else
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($permission['roles']->take(4) as $role)
                                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $colorClasses[$role->color] ?? $colorClasses['teal'] }}">
                                                    {{ $role->name }}
                                                </span>
                                            @endforeach
                                            @if ($permission['roles']->count() > 4)
                                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">
                                                    +{{ $permission['roles']->count() - 4 }} more
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex justify-end gap-2">
                                        @if (auth()->user()?->hasPermission('permissions.manage'))
                                            @if (! empty($permission['id']))
                                                <a href="{{ route('admin.permission-catalog.permissions.edit', $permission['id']) }}" aria-label="Edit permission" title="Edit permission" class="group relative inline-flex size-9 items-center justify-center rounded-md border border-teal-100 bg-white text-teal-700 transition hover:bg-teal-50 hover:text-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-300">
                                                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4 20 4.5-1 10-10a2.12 2.12 0 0 0-3-3l-10 10L4 20Z"/><path stroke-linecap="round" stroke-linejoin="round" d="m14 7 3 3"/></svg>
                                                    <span class="pointer-events-none absolute bottom-full right-0 z-10 mb-2 whitespace-nowrap rounded-md bg-slate-950 px-2.5 py-1.5 text-xs font-bold text-white opacity-0 shadow-lg transition group-hover:opacity-100 group-focus:opacity-100">Edit</span>
                                                </a>
                                            @endif
                                            <a href="{{ route('admin.permissions.edit', $permission['key']) }}" aria-label="Manage roles" title="Manage roles" class="group relative inline-flex size-9 items-center justify-center rounded-md border border-teal-100 bg-white text-teal-700 transition hover:bg-teal-50 hover:text-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-300">
                                                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                                <span class="pointer-events-none absolute bottom-full right-0 z-10 mb-2 whitespace-nowrap rounded-md bg-slate-950 px-2.5 py-1.5 text-xs font-bold text-white opacity-0 shadow-lg transition group-hover:opacity-100 group-focus:opacity-100">Manage roles</span>
                                            </a>
                                            @if (! empty($permission['id']) && empty($permission['is_system']))
                                                <form method="POST" action="{{ route('admin.permission-catalog.permissions.destroy', $permission['id']) }}" onsubmit="return confirm('Delete this permission and remove it from all roles?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" aria-label="Delete permission" title="Delete permission" class="group relative inline-flex size-9 items-center justify-center rounded-md border border-rose-100 bg-white text-rose-600 transition hover:bg-rose-50 hover:text-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-300">
                                                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5h6v2m-7 3v8m4-8v8m4-8v8M8 21h8a1 1 0 0 0 1-1V7H7v13a1 1 0 0 0 1 1Z"/></svg>
                                                        <span class="pointer-events-none absolute bottom-full right-0 z-10 mb-2 whitespace-nowrap rounded-md bg-slate-950 px-2.5 py-1.5 text-xs font-bold text-white opacity-0 shadow-lg transition group-hover:opacity-100 group-focus:opacity-100">Delete</span>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-12 text-center">
                                    <p class="text-base font-bold text-slate-900">No permissions found</p>
                                    <p class="mt-1 text-sm text-slate-500">Try a different search or clear your filters.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $permissions->links() }}
            </div>
        </section>
    </div>
@endsection
