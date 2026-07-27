@csrf

@php
    $customersPayload = collect($directoryCustomers ?? [])
        ->mapWithKeys(fn ($customer) => [$customer->id => $customer->toInstallerFormPayload()])
        ->all();
@endphp

<div
    class="grid gap-4 sm:grid-cols-2"
    x-data="{
        customers: {{ Js::from($customersPayload) }},
        selectedId: '{{ old('crm_customer_id', $installation->crm_customer_id) }}',
        customer_name: @js(old('customer_name', $installation->customer_name)),
        customer_email: @js(old('customer_email', $installation->customer_email)),
        customer_phone: @js(old('customer_phone', $installation->customer_phone)),
        street_address: @js(old('street_address', $installation->street_address)),
        city: @js(old('city', $installation->city)),
        state: @js(old('state', $installation->state)),
        postal_code: @js(old('postal_code', $installation->postal_code)),
        applyCustomer() {
            if (! this.selectedId) {
                return;
            }

            const customer = this.customers[this.selectedId];
            if (! customer) {
                return;
            }

            this.customer_name = customer.name || '';
            this.customer_email = customer.email || '';
            this.customer_phone = customer.phone || '';
            this.street_address = customer.street_address || '';
            this.city = customer.city || '';
            this.state = customer.state || '';
            this.postal_code = customer.postal_code || '';
        }
    }"
>
    <div class="sm:col-span-2">
        <label class="block text-sm font-semibold text-slate-700" for="{{ $prefix }}crm_customer_id">Customer</label>
        <select
            class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
            id="{{ $prefix }}crm_customer_id"
            name="crm_customer_id"
            x-model="selectedId"
            @change="applyCustomer()"
        >
            <option value="">Select a customer (optional)</option>
            @foreach (($directoryCustomers ?? []) as $customer)
                <option value="{{ $customer->id }}" @selected((string) old('crm_customer_id', $installation->crm_customer_id) === (string) $customer->id)>
                    {{ $customer->fullName() }}@if($customer->city) — {{ $customer->city }}, {{ $customer->state }}@endif
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">Selecting a CRM customer fills the job fields from that single record.</p>
        @error('crm_customer_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700" for="{{ $prefix }}status">Status *</label>
        <select class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" id="{{ $prefix }}status" name="status" required>
            @foreach ($installationStatuses as $value => $label)
                <option @selected(old('status', $installation->status?->value ?? $installation->status) === $value) value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        @error('status')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700" for="{{ $prefix }}scheduled_at">Scheduled at</label>
        <input class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" id="{{ $prefix }}scheduled_at" name="scheduled_at" type="datetime-local" value="{{ old('scheduled_at', optional($installation->scheduled_at)->format('Y-m-d\TH:i')) }}">
        @error('scheduled_at')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700" for="{{ $prefix }}customer_name">Customer name</label>
        <input class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" id="{{ $prefix }}customer_name" name="customer_name" type="text" x-model="customer_name">
        @error('customer_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700" for="{{ $prefix }}customer_phone">Customer phone</label>
        <input class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" id="{{ $prefix }}customer_phone" name="customer_phone" type="text" x-model="customer_phone">
        @error('customer_phone')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-semibold text-slate-700" for="{{ $prefix }}customer_email">Customer email</label>
        <input class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" id="{{ $prefix }}customer_email" name="customer_email" type="email" x-model="customer_email">
        @error('customer_email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-semibold text-slate-700" for="{{ $prefix }}street_address">Street address</label>
        <input class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" id="{{ $prefix }}street_address" name="street_address" type="text" x-model="street_address">
        @error('street_address')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700" for="{{ $prefix }}city">City</label>
        <input class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" id="{{ $prefix }}city" name="city" type="text" x-model="city">
        @error('city')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700" for="{{ $prefix }}state">State</label>
        <select class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" id="{{ $prefix }}state" name="state" x-model="state">
            <option value="">Select a state</option>
            @foreach (($states ?? []) as $value => $label)
                <option value="{{ $value }}">{{ $value }} — {{ $label }}</option>
            @endforeach
        </select>
        @error('state')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700" for="{{ $prefix }}postal_code">Postal code</label>
        <input class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" id="{{ $prefix }}postal_code" name="postal_code" type="text" x-model="postal_code">
        @error('postal_code')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700" for="{{ $prefix }}completed_at">Completed at</label>
        <input class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" id="{{ $prefix }}completed_at" name="completed_at" type="datetime-local" value="{{ old('completed_at', optional($installation->completed_at)->format('Y-m-d\TH:i')) }}">
        @error('completed_at')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700" for="{{ $prefix }}cancelled_at">Cancelled at</label>
        <input class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" id="{{ $prefix }}cancelled_at" name="cancelled_at" type="datetime-local" value="{{ old('cancelled_at', optional($installation->cancelled_at)->format('Y-m-d\TH:i')) }}">
        @error('cancelled_at')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700" for="{{ $prefix }}rescheduled_at">Rescheduled at</label>
        <input class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" id="{{ $prefix }}rescheduled_at" name="rescheduled_at" type="datetime-local" value="{{ old('rescheduled_at', optional($installation->rescheduled_at)->format('Y-m-d\TH:i')) }}">
        @error('rescheduled_at')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-semibold text-slate-700" for="{{ $prefix }}notes">Notes</label>
        <textarea class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500" id="{{ $prefix }}notes" name="notes" rows="3">{{ old('notes', $installation->notes) }}</textarea>
        @error('notes')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>
