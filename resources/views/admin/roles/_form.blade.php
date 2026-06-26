@csrf

@php
    $selectedPermissions = old('permissions', $role->permissions ?? []);
    $selectedUsers = collect(old('user_ids', $role->exists ? $role->users->pluck('id')->all() : []))->map(fn ($id) => (int) $id)->all();
@endphp

<div class="grid gap-6 lg:grid-cols-[1fr_0.8fr]">
    <div class="space-y-5">
        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <label for="name" class="block text-sm font-semibold text-slate-700">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $role->name) }}" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" placeholder="Content Manager">
                @error('name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="slug" class="block text-sm font-semibold text-slate-700">Slug</label>
                <input id="slug" name="slug" type="text" value="{{ old('slug', $role->slug) }}" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" placeholder="content-manager">
                <p class="mt-2 text-xs text-slate-500">Leave blank to generate from the role name.</p>
                @error('slug')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="status" class="block text-sm font-semibold text-slate-700">Status</label>
                <select id="status" name="status" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $role->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="color" class="block text-sm font-semibold text-slate-700">Color</label>
                <select id="color" name="color" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    @foreach ($colors as $value => $label)
                        <option value="{{ $value }}" @selected(old('color', $role->color) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('color')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label for="description" class="block text-sm font-semibold text-slate-700">Description</label>
            <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" placeholder="What this role is responsible for.">{{ old('description', $role->description) }}</textarea>
            @error('description')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <label class="flex items-center gap-3 rounded-md border border-teal-100 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-900">
            <input type="checkbox" name="is_system" value="1" @checked(old('is_system', $role->is_system)) class="rounded border-teal-200 text-teal-600 focus:ring-teal-500">
            Protect as system role
        </label>

        <div>
            <p class="text-sm font-semibold text-slate-700">Permissions</p>
            <div class="mt-3 grid gap-4 md:grid-cols-2">
                @foreach ($permissions as $group)
                    <div class="rounded-lg border border-teal-100 bg-white p-4 shadow-sm">
                        <p class="text-sm font-black text-slate-950">{{ $group['label'] }}</p>
                        <div class="mt-3 space-y-2">
                            @foreach ($group['items'] as $key => $label)
                                <label class="flex items-start gap-3 rounded-md px-2 py-1.5 text-sm text-slate-700 transition hover:bg-teal-50">
                                    <input type="checkbox" name="permissions[]" value="{{ $key }}" @checked(in_array($key, $selectedPermissions, true)) class="mt-0.5 rounded border-teal-200 text-teal-600 focus:ring-teal-500">
                                    <span>
                                        <span class="block font-semibold text-slate-900">{{ $label }}</span>
                                        <span class="block text-xs text-slate-400">{{ $key }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            @error('permissions')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <aside class="space-y-5">
        <div class="rounded-lg border border-teal-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Assigned Users</p>
            <div class="mt-4 max-h-[34rem] space-y-2 overflow-y-auto pr-1">
                @forelse ($users as $user)
                    <label class="flex items-center gap-3 rounded-md border border-slate-100 px-3 py-2 text-sm transition hover:bg-teal-50">
                        <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" @checked(in_array($user->id, $selectedUsers, true)) class="rounded border-teal-200 text-teal-600 focus:ring-teal-500">
                        <span class="min-w-0">
                            <span class="block truncate font-semibold text-slate-900">{{ $user->name }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $user->email }}</span>
                        </span>
                    </label>
                @empty
                    <p class="rounded-md border border-dashed border-teal-200 bg-teal-50 px-4 py-6 text-center text-sm font-semibold text-teal-800">No users available.</p>
                @endforelse
            </div>
            @error('user_ids')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </aside>
</div>

<div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
    <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-5 py-2.5 text-sm font-bold text-teal-800 shadow-sm transition hover:bg-teal-50">
        Cancel
    </a>
    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-2.5 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
        {{ $submitLabel }}
    </button>
</div>
