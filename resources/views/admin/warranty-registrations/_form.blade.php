@csrf

<div class="grid gap-5 lg:grid-cols-2">
    <div>
        <label for="customer_name" class="block text-sm font-semibold text-slate-700">Customer Name</label>
        <input id="customer_name" name="customer_name" type="text" value="{{ old('customer_name', $registration->customer_name) }}" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('customer_name')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="phone" class="block text-sm font-semibold text-slate-700">Phone</label>
        <input id="phone" name="phone" type="text" value="{{ old('phone', $registration->phone) }}" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('phone')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2">
        <label for="email" class="block text-sm font-semibold text-slate-700">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $registration->email) }}" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('email')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="serial_number" class="block text-sm font-semibold text-slate-700">Serial Number</label>
        <input id="serial_number" name="serial_number" type="text" value="{{ old('serial_number', $registration->serial_number) }}" required class="mt-1 block w-full rounded-md border-teal-100 font-mono text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('serial_number')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="machine_model" class="block text-sm font-semibold text-slate-700">Machine Model</label>
        <input id="machine_model" name="machine_model" type="text" value="{{ old('machine_model', $registration->machine_model) }}" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('machine_model')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="purchase_date" class="block text-sm font-semibold text-slate-700">Purchase Date</label>
        <input id="purchase_date" name="purchase_date" type="date" value="{{ old('purchase_date', $registration->purchase_date?->format('Y-m-d')) }}" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('purchase_date')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="purchased_from" class="block text-sm font-semibold text-slate-700">Purchased From</label>
        <input id="purchased_from" name="purchased_from" type="text" value="{{ old('purchased_from', $registration->purchased_from) }}" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" placeholder="Dealer or retailer (optional)">
        @error('purchased_from')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2">
        <label for="notes" class="block text-sm font-semibold text-slate-700">Notes</label>
        <textarea id="notes" name="notes" rows="5" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" placeholder="Internal notes (optional)">{{ old('notes', $registration->notes) }}</textarea>
        @error('notes')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
    <a href="{{ route('admin.warranty-registrations.show', $registration) }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-5 py-2.5 text-sm font-bold text-teal-800 shadow-sm transition hover:bg-teal-50">
        Cancel
    </a>
    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-2.5 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
        {{ $submitLabel }}
    </button>
</div>
