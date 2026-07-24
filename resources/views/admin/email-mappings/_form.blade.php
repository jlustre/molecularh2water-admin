@csrf

@php
    $allowMultipleEmails = $allowMultipleEmails ?? false;
    $defaultEmails = $recipientEmails
        ?? [old('email', $mapping->email ?? '')];
    $oldEmails = old('emails', $defaultEmails);
    if (! is_array($oldEmails) || $oldEmails === []) {
        $oldEmails = [''];
    }
@endphp

<div class="grid gap-5">
    <div>
        <label for="form_key" class="block text-sm font-semibold text-slate-700">Form</label>
        <select id="form_key" name="form_key" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
            @foreach ($formOptions as $value => $label)
                <option @selected(old('form_key', $mapping->form_key?->value) === $value) value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        @error('form_key')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    @if ($allowMultipleEmails)
        <div
            class="space-y-3"
            x-data="{
                emails: @js(array_values($oldEmails)),
                add() {
                    this.emails.push('');
                },
                remove(index) {
                    if (this.emails.length === 1) {
                        this.emails = [''];
                        return;
                    }
                    this.emails.splice(index, 1);
                }
            }"
        >
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="block text-sm font-semibold text-slate-700">Recipient emails</p>
                    <p class="mt-1 text-xs font-medium text-slate-500">Add one or more emails. Every active recipient is notified when this form is submitted.</p>
                </div>
                <button
                    class="inline-flex items-center justify-center gap-1.5 rounded-md border border-teal-200 bg-white px-3 py-2 text-sm font-bold text-teal-800 transition hover:bg-teal-50"
                    type="button"
                    @click="add()"
                >
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 5v14M5 12h14" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Add email
                </button>
            </div>

            <template x-for="(email, index) in emails" :key="index">
                <div class="flex items-start gap-2">
                    <div class="min-w-0 flex-1">
                        <input
                            :id="'email_'+index"
                            :name="'emails['+index+']'"
                            type="email"
                            required
                            maxlength="255"
                            x-model="emails[index]"
                            class="mt-0 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                            placeholder="recipient@example.com"
                        >
                    </div>
                    <button
                        class="inline-flex size-10 shrink-0 items-center justify-center rounded-md border border-red-100 text-red-600 transition hover:bg-red-50"
                        type="button"
                        title="Remove email"
                        @click="remove(index)"
                        :disabled="emails.length === 1"
                    >
                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path d="M5 7h14M10 11v6M14 11v6M8 7l1-3h6l1 3M7 7l1 13h8l1-13" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
            </template>

            @error('emails')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error('emails.*')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="name" class="block text-sm font-semibold text-slate-700">Shared recipient label</label>
            <input
                id="name"
                name="name"
                type="text"
                maxlength="255"
                value="{{ old('name', $mapping->name) }}"
                class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                placeholder="Shipping Team"
            >
            <p class="mt-2 text-xs font-medium text-slate-500">Optional label applied to every email added above.</p>
            @error('name')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="email" class="block text-sm font-semibold text-slate-700">Email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    required
                    maxlength="255"
                    value="{{ old('email', $mapping->email) }}"
                    class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                    placeholder="shipping@happycooking.com"
                >
                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="name" class="block text-sm font-semibold text-slate-700">Recipient name</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    maxlength="255"
                    value="{{ old('name', $mapping->name) }}"
                    class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                    placeholder="Shipping Team"
                >
                @error('name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    @endif

    <div>
        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
            <input
                class="rounded border-teal-200 text-teal-600 focus:ring-teal-500"
                name="is_active"
                type="checkbox"
                value="1"
                @checked(old('is_active', $mapping->is_active ? '1' : null))
            >
            Active (receive notifications)
        </label>
        @error('is_active')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="notes" class="block text-sm font-semibold text-slate-700">Notes</label>
        <textarea
            id="notes"
            name="notes"
            rows="4"
            maxlength="2000"
            class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
            placeholder="Optional internal note about why this recipient is mapped."
        >{{ old('notes', $mapping->notes) }}</textarea>
        @error('notes')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-wrap gap-3 pt-2">
        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-2.5 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
            {{ $submitLabel }}
        </button>
        <a href="{{ route('admin.email-mappings.index') }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-5 py-2.5 text-sm font-bold text-teal-800 shadow-sm transition hover:bg-teal-50">
            Cancel
        </a>
    </div>
</div>
