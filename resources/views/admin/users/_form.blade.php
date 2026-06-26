@csrf

<div class="grid gap-5 lg:grid-cols-2">
    <div>
        <label for="name" class="block text-sm font-semibold text-slate-700">Name</label>
        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" placeholder="Jane Smith">
        @error('name')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="email" class="block text-sm font-semibold text-slate-700">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" placeholder="jane@example.com">
        @error('email')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
        <input id="password" name="password" type="password" @required(! $user->exists) class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" autocomplete="new-password">
        <p class="mt-2 text-xs text-slate-500">{{ $user->exists ? 'Leave blank to keep the current password.' : 'Use a secure password for the new account.' }}</p>
        @error('password')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password_confirmation" class="block text-sm font-semibold text-slate-700">Confirm Password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" @required(! $user->exists) class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" autocomplete="new-password">
    </div>

    <div class="lg:col-span-2">
        <label for="email_status" class="block text-sm font-semibold text-slate-700">Email Status</label>
        <select id="email_status" name="email_status" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
            <option value="verified" @selected(old('email_status', $user->email_verified_at ? 'verified' : 'unverified') === 'verified')>Verified</option>
            <option value="unverified" @selected(old('email_status', $user->email_verified_at ? 'verified' : 'unverified') === 'unverified')>Unverified</option>
        </select>
        @error('email_status')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-5 py-2.5 text-sm font-bold text-teal-800 shadow-sm transition hover:bg-teal-50">
        Cancel
    </a>
    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-2.5 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
        {{ $submitLabel }}
    </button>
</div>
