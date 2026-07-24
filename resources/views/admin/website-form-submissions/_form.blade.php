@csrf

<div class="grid gap-5 lg:grid-cols-2">
    <div>
        <label for="status" class="block text-sm font-semibold text-slate-700">Status</label>
        <select id="status" name="status" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $submission->status?->value ?? 'new') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="name" class="block text-sm font-semibold text-slate-700">Name</label>
        <input id="name" name="name" type="text" value="{{ old('name', $submission->name) }}" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="email" class="block text-sm font-semibold text-slate-700">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $submission->email) }}" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="phone" class="block text-sm font-semibold text-slate-700">Phone</label>
        <input id="phone" name="phone" type="text" value="{{ old('phone', $submission->phone) }}" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('phone')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="referrer_name" class="block text-sm font-semibold text-slate-700">Referrer Name</label>
        <input id="referrer_name" name="referrer_name" type="text" value="{{ old('referrer_name', $submission->referrer_name) }}" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('referrer_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="preferred_time" class="block text-sm font-semibold text-slate-700">Preferred Day / Time</label>
        <input id="preferred_time" name="preferred_time" type="text" value="{{ old('preferred_time', $submission->preferred_time) }}" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('preferred_time')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="lg:col-span-2">
        <label for="interested_in" class="block text-sm font-semibold text-slate-700">Interested In</label>
        <input id="interested_in" name="interested_in" type="text" value="{{ old('interested_in', $submission->interested_in) }}" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('interested_in')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="lg:col-span-2">
        <label for="message" class="block text-sm font-semibold text-slate-700">Message</label>
        <textarea id="message" name="message" rows="4" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">{{ old('message', $submission->message) }}</textarea>
        @error('message')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="source" class="block text-sm font-semibold text-slate-700">Source</label>
        <input id="source" name="source" type="text" value="{{ old('source', $submission->source) }}" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('source')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="tracking_source" class="block text-sm font-semibold text-slate-700">Tracking Source</label>
        <input id="tracking_source" name="tracking_source" type="text" value="{{ old('tracking_source', $submission->tracking_source) }}" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('tracking_source')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="lg:col-span-2">
        <label for="page_url" class="block text-sm font-semibold text-slate-700">Page URL</label>
        <input id="page_url" name="page_url" type="text" value="{{ old('page_url', $submission->page_url) }}" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('page_url')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="lg:col-span-2">
        <label for="admin_notes" class="block text-sm font-semibold text-slate-700">Admin Notes</label>
        <textarea id="admin_notes" name="admin_notes" rows="3" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">{{ old('admin_notes', $submission->admin_notes) }}</textarea>
        @error('admin_notes')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="lg:col-span-2">
        <label class="flex items-center gap-3 rounded-md border border-teal-100 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-900">
            <input type="checkbox" name="consent_given" value="1" @checked(old('consent_given', $submission->consent_given)) class="rounded border-teal-200 text-teal-600 focus:ring-teal-500">
            Consent given to be contacted
        </label>
    </div>
</div>

<div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
    <a href="{{ route('admin.website-forms.index', $formType->routeKey()) }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-5 py-2.5 text-sm font-bold text-teal-800 shadow-sm transition hover:bg-teal-50">
        Cancel
    </a>
    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-2.5 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
        {{ $submitLabel }}
    </button>
</div>
