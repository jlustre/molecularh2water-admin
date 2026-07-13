@csrf

<div class="grid gap-5">
    <div>
        <label for="question" class="block text-sm font-semibold text-slate-700">Question</label>
        <input
            id="question"
            name="question"
            type="text"
            value="{{ old('question', $faq->question) }}"
            required
            maxlength="500"
            class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
            placeholder="What is hydrogen water?"
        >
        @error('question')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="answer" class="block text-sm font-semibold text-slate-700">Answer</label>
        <textarea
            id="answer"
            name="answer"
            rows="8"
            required
            class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
            placeholder="Write a clear educational answer. HTML paragraphs are supported."
        >{{ old('answer', $faq->answer) }}</textarea>
        <p class="mt-2 text-xs font-medium text-slate-500">Plain text or simple HTML like &lt;p&gt; tags is supported on the website.</p>
        @error('answer')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="status" class="block text-sm font-semibold text-slate-700">Status</label>
            <select id="status" name="status" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $faq->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="sort_order" class="block text-sm font-semibold text-slate-700">Sort order</label>
            <input
                id="sort_order"
                name="sort_order"
                type="number"
                min="0"
                max="9999"
                value="{{ old('sort_order', $faq->sort_order) }}"
                required
                class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
            >
            <p class="mt-2 text-xs font-medium text-slate-500">Lower numbers appear first on the website.</p>
            @error('sort_order')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex flex-wrap gap-3 pt-2">
        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-2.5 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
            {{ $submitLabel }}
        </button>
        <a href="{{ route('admin.faqs.index') }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-5 py-2.5 text-sm font-bold text-teal-800 shadow-sm transition hover:bg-teal-50">
            Cancel
        </a>
    </div>
</div>
