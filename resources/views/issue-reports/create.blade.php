@extends('layouts.portal')

@section('content')
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="mx-auto max-w-4xl space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200/30 bg-emerald-400/10 px-4 py-3 text-sm font-semibold text-emerald-100">
                    {{ session('status') }}
                </div>
            @endif

            <section class="overflow-hidden rounded-2xl border border-teal-200/20 bg-gradient-to-br from-[#041f1e] via-[#062926] to-[#0a3d38] p-6 text-white shadow-xl sm:p-8">
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-300/80">Support</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight">Report An Issue</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-teal-100/80">
                    Tell us about an error, broken page, or anything that needs attention on the public website or this admin/member portal. You will receive a confirmation email and another email whenever the status changes.
                </p>
            </section>

            <section class="rounded-2xl border border-teal-200/20 bg-white p-6 shadow-xl sm:p-8">
                <form method="POST" action="{{ route('issue-reports.store') }}" enctype="multipart/form-data" class="grid gap-5">
                    @csrf

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="reporter_name" class="block text-sm font-semibold text-slate-700">Your name</label>
                            <input id="reporter_name" name="reporter_name" type="text" value="{{ old('reporter_name', $defaults['reporter_name']) }}" required maxlength="120" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                            @error('reporter_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="reporter_email" class="block text-sm font-semibold text-slate-700">Email</label>
                            <input id="reporter_email" name="reporter_email" type="email" value="{{ old('reporter_email', $defaults['reporter_email']) }}" required maxlength="255" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                            @error('reporter_email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="reporter_phone" class="block text-sm font-semibold text-slate-700">Phone</label>
                        <input id="reporter_phone" name="reporter_phone" type="text" value="{{ old('reporter_phone') }}" maxlength="50" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <div class="grid gap-5 sm:grid-cols-3">
                        <div>
                            <label for="site" class="block text-sm font-semibold text-slate-700">Where did this happen?</label>
                            <select id="site" name="site" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                @foreach ($sites as $value => $label)
                                    <option value="{{ $value }}" @selected(old('site', $defaults['site']) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('site')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="category" class="block text-sm font-semibold text-slate-700">Category</label>
                            <select id="category" name="category" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                @foreach ($categories as $value => $label)
                                    <option value="{{ $value }}" @selected(old('category', 'bug') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="severity" class="block text-sm font-semibold text-slate-700">Severity</label>
                            <select id="severity" name="severity" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                                @foreach ($severities as $value => $label)
                                    <option value="{{ $value }}" @selected(old('severity', 'medium') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="title" class="block text-sm font-semibold text-slate-700">Short title</label>
                        <input id="title" name="title" type="text" value="{{ old('title') }}" required maxlength="180" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                        @error('title')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-semibold text-slate-700">What happened?</label>
                        <textarea id="description" name="description" rows="6" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">{{ old('description') }}</textarea>
                        @error('description')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="page_url" class="block text-sm font-semibold text-slate-700">Page URL</label>
                        <input id="page_url" name="page_url" type="text" value="{{ old('page_url', url()->current()) }}" maxlength="500" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <div class="grid gap-5 lg:grid-cols-3">
                        <div>
                            <label for="steps_to_reproduce" class="block text-sm font-semibold text-slate-700">Steps to reproduce</label>
                            <textarea id="steps_to_reproduce" name="steps_to_reproduce" rows="4" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">{{ old('steps_to_reproduce') }}</textarea>
                        </div>
                        <div>
                            <label for="expected_behavior" class="block text-sm font-semibold text-slate-700">Expected</label>
                            <textarea id="expected_behavior" name="expected_behavior" rows="4" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">{{ old('expected_behavior') }}</textarea>
                        </div>
                        <div>
                            <label for="actual_behavior" class="block text-sm font-semibold text-slate-700">Actual</label>
                            <textarea id="actual_behavior" name="actual_behavior" rows="4" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">{{ old('actual_behavior') }}</textarea>
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="browser" class="block text-sm font-semibold text-slate-700">Browser</label>
                            <input id="browser" name="browser" type="text" value="{{ old('browser') }}" maxlength="120" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                        </div>
                        <div>
                            <label for="device" class="block text-sm font-semibold text-slate-700">Device</label>
                            <input id="device" name="device" type="text" value="{{ old('device') }}" maxlength="120" class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                        </div>
                    </div>

                    <div>
                        <label for="screenshot" class="block text-sm font-semibold text-slate-700">Screenshot (optional)</label>
                        <input id="screenshot" name="screenshot" type="file" accept="image/*" class="mt-1 block w-full text-sm text-slate-700">
                        @error('screenshot')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-3 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
                        Submit Issue Report
                    </button>
                </form>
            </section>
        </div>
    </div>
@endsection
