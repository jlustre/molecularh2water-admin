@csrf

<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="name" class="block text-sm font-semibold text-slate-700">Name *</label>
        <input id="name" name="name" type="text" required value="{{ old('name', $installer->name) }}" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" placeholder="Jordan Installer">
        @error('name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="email" class="block text-sm font-semibold text-slate-700">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $installer->email) }}" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" placeholder="installer@example.com">
        @error('email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="phone" class="block text-sm font-semibold text-slate-700">Phone</label>
        <input id="phone" name="phone" type="text" value="{{ old('phone', $installer->phone) }}" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" placeholder="(555) 123-4567">
        @error('phone')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="company" class="block text-sm font-semibold text-slate-700">Company</label>
        <input id="company" name="company" type="text" value="{{ old('company', $installer->company) }}" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" placeholder="Independent / Company name">
        @error('company')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="status" class="block text-sm font-semibold text-slate-700">Status *</label>
        <select id="status" name="status" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $installer->status?->value ?? $installer->status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="city" class="block text-sm font-semibold text-slate-700">City</label>
        <input id="city" name="city" type="text" value="{{ old('city', $installer->city) }}" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('city')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="state" class="block text-sm font-semibold text-slate-700">State</label>
        <select id="state" name="state" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
            <option value="">Select a state</option>
            @foreach ($states as $value => $label)
                <option value="{{ $value }}" @selected(old('state', $installer->state ?: 'CA') === $value)>
                    {{ $value }} — {{ $label }}
                </option>
            @endforeach
        </select>
        @error('state')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="notes" class="block text-sm font-semibold text-slate-700">Notes</label>
        <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" placeholder="Service area, specialties, availability notes...">{{ old('notes', $installer->notes) }}</textarea>
        @error('notes')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
    <a href="{{ $cancelRoute }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-5 py-2.5 text-sm font-bold text-teal-800 shadow-sm transition hover:bg-teal-50">
        Cancel
    </a>
    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-2.5 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
        {{ $submitLabel }}
    </button>
</div>
