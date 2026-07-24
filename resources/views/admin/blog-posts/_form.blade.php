@csrf

<div class="grid gap-5">
    <div>
        <label for="title" class="block text-sm font-semibold text-slate-700">Title</label>
        <input
            id="title"
            name="title"
            type="text"
            value="{{ old('title', $post->title) }}"
            required
            maxlength="255"
            class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
            placeholder="The benefits of molecular hydrogen"
        >
        @error('title')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="slug" class="block text-sm font-semibold text-slate-700">Slug</label>
        <input
            id="slug"
            name="slug"
            type="text"
            value="{{ old('slug', $post->slug) }}"
            maxlength="255"
            class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
            placeholder="Leave blank to auto-generate from title"
        >
        <p class="mt-2 text-xs font-medium text-slate-500">Optional. Used in public URLs when published.</p>
        @error('slug')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="excerpt" class="block text-sm font-semibold text-slate-700">Excerpt</label>
        <textarea
            id="excerpt"
            name="excerpt"
            rows="3"
            class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
            placeholder="Short summary shown in listings."
        >{{ old('excerpt', $post->excerpt) }}</textarea>
        @error('excerpt')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="body" class="block text-sm font-semibold text-slate-700">Body</label>
        <textarea
            id="body"
            name="body"
            rows="12"
            required
            class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
            placeholder="Write the full article. Plain text or simple HTML is supported."
        >{{ old('body', $post->body) }}</textarea>
        @error('body')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-5 sm:grid-cols-3">
        <div>
            <label for="status" class="block text-sm font-semibold text-slate-700">Status</label>
            <select id="status" name="status" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $post->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="published_at" class="block text-sm font-semibold text-slate-700">Published at</label>
            <input
                id="published_at"
                name="published_at"
                type="datetime-local"
                value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}"
                class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
            >
            @error('published_at')
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
                value="{{ old('sort_order', $post->sort_order) }}"
                required
                class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
            >
            <p class="mt-2 text-xs font-medium text-slate-500">Lower numbers appear first.</p>
            @error('sort_order')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex flex-wrap gap-3 pt-2">
        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-2.5 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
            {{ $submitLabel }}
        </button>
        <a href="{{ route('admin.blog.index') }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-5 py-2.5 text-sm font-bold text-teal-800 shadow-sm transition hover:bg-teal-50">
            Cancel
        </a>
    </div>
</div>
