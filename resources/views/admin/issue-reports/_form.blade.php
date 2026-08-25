@csrf

@if ($report->exists)
    @method('PUT')
@endif

<div class="grid gap-5">
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="reporter_name" class="block text-sm font-semibold text-slate-700">Reporter name</label>
            <input id="reporter_name" name="reporter_name" type="text" value="{{ old('reporter_name', $report->reporter_name) }}" required maxlength="120" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
            @error('reporter_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="reporter_email" class="block text-sm font-semibold text-slate-700">Reporter email</label>
            <input id="reporter_email" name="reporter_email" type="email" value="{{ old('reporter_email', $report->reporter_email) }}" required maxlength="255" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
            @error('reporter_email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="reporter_phone" class="block text-sm font-semibold text-slate-700">Phone</label>
        <input id="reporter_phone" name="reporter_phone" type="text" value="{{ old('reporter_phone', $report->reporter_phone) }}" maxlength="50" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('reporter_phone')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="grid gap-5 sm:grid-cols-3">
        <div>
            <label for="site" class="block text-sm font-semibold text-slate-700">Site</label>
            <select id="site" name="site" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                @foreach ($sites as $value => $label)
                    <option value="{{ $value }}" @selected(old('site', $report->site?->value) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('site')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="category" class="block text-sm font-semibold text-slate-700">Category</label>
            <select id="category" name="category" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                @foreach ($categories as $value => $label)
                    <option value="{{ $value }}" @selected(old('category', $report->category?->value) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('category')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="severity" class="block text-sm font-semibold text-slate-700">Severity</label>
            <select id="severity" name="severity" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                @foreach ($severities as $value => $label)
                    <option value="{{ $value }}" @selected(old('severity', $report->severity?->value) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('severity')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="title" class="block text-sm font-semibold text-slate-700">Title</label>
        <input id="title" name="title" type="text" value="{{ old('title', $report->title) }}" required maxlength="180" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('title')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="description" class="block text-sm font-semibold text-slate-700">Description</label>
        <textarea id="description" name="description" rows="6" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">{{ old('description', $report->description) }}</textarea>
        @error('description')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="page_url" class="block text-sm font-semibold text-slate-700">Page URL</label>
        <input id="page_url" name="page_url" type="text" value="{{ old('page_url', $report->page_url) }}" maxlength="500" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        @error('page_url')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div>
            <label for="steps_to_reproduce" class="block text-sm font-semibold text-slate-700">Steps to reproduce</label>
            <textarea id="steps_to_reproduce" name="steps_to_reproduce" rows="4" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">{{ old('steps_to_reproduce', $report->steps_to_reproduce) }}</textarea>
        </div>
        <div>
            <label for="expected_behavior" class="block text-sm font-semibold text-slate-700">Expected</label>
            <textarea id="expected_behavior" name="expected_behavior" rows="4" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">{{ old('expected_behavior', $report->expected_behavior) }}</textarea>
        </div>
        <div>
            <label for="actual_behavior" class="block text-sm font-semibold text-slate-700">Actual</label>
            <textarea id="actual_behavior" name="actual_behavior" rows="4" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">{{ old('actual_behavior', $report->actual_behavior) }}</textarea>
        </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="browser" class="block text-sm font-semibold text-slate-700">Browser</label>
            <input id="browser" name="browser" type="text" value="{{ old('browser', $report->browser) }}" maxlength="120" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        </div>
        <div>
            <label for="device" class="block text-sm font-semibold text-slate-700">Device</label>
            <input id="device" name="device" type="text" value="{{ old('device', $report->device) }}" maxlength="120" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        </div>
    </div>

    <div>
        <label for="screenshot" class="block text-sm font-semibold text-slate-700">Screenshot</label>
        <input id="screenshot" name="screenshot" type="file" accept="image/*" class="mt-1 block w-full text-sm text-slate-700">
        @if ($report->screenshotUrl())
            <p class="mt-2 text-xs font-medium text-slate-500">A screenshot is already attached. Uploading a new file replaces it.</p>
        @endif
        @error('screenshot')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="status" class="block text-sm font-semibold text-slate-700">Status</label>
            <select id="status" name="status" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $report->status?->value) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="assigned_to_user_id" class="block text-sm font-semibold text-slate-700">Assigned to</label>
            <select id="assigned_to_user_id" name="assigned_to_user_id" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                <option value="">Unassigned</option>
                @foreach ($assignees as $assignee)
                    <option value="{{ $assignee->id }}" @selected((string) old('assigned_to_user_id', $report->assigned_to_user_id) === (string) $assignee->id)>{{ $assignee->name }} ({{ $assignee->email }})</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label for="admin_notes" class="block text-sm font-semibold text-slate-700">Internal notes</label>
        <textarea id="admin_notes" name="admin_notes" rows="4" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">{{ old('admin_notes', $report->admin_notes) }}</textarea>
        <p class="mt-2 text-xs font-medium text-slate-500">Internal only. This is not emailed to the reporter.</p>
    </div>

    <div>
        <label for="resolution_summary" class="block text-sm font-semibold text-slate-700">Resolution summary</label>
        <textarea id="resolution_summary" name="resolution_summary" rows="4" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">{{ old('resolution_summary', $report->resolution_summary) }}</textarea>
        <p class="mt-2 text-xs font-medium text-slate-500">Included in the status-update email when the status changes.</p>
        @error('resolution_summary')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <label class="inline-flex items-center gap-3 text-sm font-semibold text-slate-700">
        <input type="hidden" name="notify_reporter" value="0">
        <input type="checkbox" name="notify_reporter" value="1" @checked((string) old('notify_reporter', '1') === '1') class="rounded border-teal-200 text-teal-600 focus:ring-teal-500">
        Email the reporter if the status changes
    </label>

    <div class="flex flex-wrap gap-3 pt-2">
        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-2.5 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
            {{ $submitLabel }}
        </button>
        <a href="{{ $report->exists ? route('admin.issue-reports.show', $report) : route('admin.issue-reports.index') }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-5 py-2.5 text-sm font-bold text-teal-800 shadow-sm transition hover:bg-teal-50">
            Cancel
        </a>
    </div>
</div>
