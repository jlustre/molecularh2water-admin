@csrf

@php
    $postalCode = old('postal_code', is_array($customer->metadata ?? null) ? ($customer->metadata['postal_code'] ?? null) : null);
@endphp

<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="name" class="block text-sm font-semibold text-slate-700">Name *</label>
        <input id="name" name="name" type="text" required value="{{ old('name', $customer->exists ? $customer->fullName() : '') }}" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" placeholder="Jordan Customer">
        @error('name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="email" class="block text-sm font-semibold text-slate-700">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $customer->email) }}" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="phone" class="block text-sm font-semibold text-slate-700">Phone</label>
        <input id="phone" name="phone" type="text" value="{{ old('phone', $customer->phone) }}" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('phone')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="street_address" class="block text-sm font-semibold text-slate-700">Street address</label>
        <input id="street_address" name="street_address" type="text" value="{{ old('street_address', $customer->address) }}" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('street_address')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="city" class="block text-sm font-semibold text-slate-700">City</label>
        <input id="city" name="city" type="text" value="{{ old('city', $customer->city) }}" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('city')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="state" class="block text-sm font-semibold text-slate-700">State</label>
        <select id="state" name="state" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
            <option value="">Select a state</option>
            @foreach ($states as $value => $label)
                <option value="{{ $value }}" @selected(old('state', $customer->state ?: 'CA') === $value)>
                    {{ $value }} — {{ $label }}
                </option>
            @endforeach
        </select>
        @error('state')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="postal_code" class="block text-sm font-semibold text-slate-700">Postal code</label>
        <input id="postal_code" name="postal_code" type="text" value="{{ $postalCode }}" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('postal_code')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="assigned_user_id" class="block text-sm font-semibold text-slate-700">Consultant</label>
        <select id="assigned_user_id" name="assigned_user_id" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
            <option value="">Unassigned</option>
            @foreach ($consultants as $consultant)
                <option value="{{ $consultant->id }}" @selected((string) old('assigned_user_id', $customer->assigned_user_id) === (string) $consultant->id)>
                    {{ $consultant->name }}
                </option>
            @endforeach
        </select>
        @error('assigned_user_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="engagement_type" class="block text-sm font-semibold text-slate-700">Type *</label>
        <select id="engagement_type" name="engagement_type" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
            @foreach ($engagementTypes as $value => $label)
                @if ($value !== 'R')
                    <option value="{{ $value }}" @selected(old('engagement_type', $customer->engagement_type?->value ?? 'C') === $value)>{{ $value }} — {{ $label }}</option>
                @endif
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">C = customer only. B = customer and recruit (same record).</p>
        @error('engagement_type')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="notes" class="block text-sm font-semibold text-slate-700">Notes</label>
        <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">{{ old('notes', $customer->message) }}</textarea>
        @error('notes')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
    <a href="{{ route('admin.customers.index') }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-5 py-2.5 text-sm font-bold text-teal-800 shadow-sm transition hover:bg-teal-50">
        Cancel
    </a>
    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-2.5 text-sm font-bold text-[#031a19] transition hover:bg-teal-300">
        {{ $submitLabel }}
    </button>
</div>
