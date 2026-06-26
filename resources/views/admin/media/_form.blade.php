@csrf

@php
    $isVideoLinkForm = $isVideoLinkForm ?? false;
    $isMediaLinkForm = $isMediaLinkForm ?? false;
    $isLinkOnlyForm = $isVideoLinkForm || $isMediaLinkForm;
@endphp

<div class="grid gap-5 lg:grid-cols-2">
    <div class="lg:col-span-2">
        <label for="title" class="block text-sm font-semibold text-slate-700">Title</label>
        <input
            id="title"
            name="title"
            type="text"
            value="{{ old('title', $mediaItem->title) }}"
            required
            class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
            placeholder="Hydrogen Water Guide"
        >
        @error('title')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    @if ($isLinkOnlyForm)
        <div>
            <input type="hidden" name="category" value="{{ $isVideoLinkForm ? 'videos' : 'links' }}">
            <span class="block text-sm font-semibold text-slate-700">Category</span>
            <div class="mt-1 rounded-md border border-teal-100 bg-teal-50 px-4 py-2.5 text-sm font-bold text-teal-900">
                {{ $isVideoLinkForm ? 'Videos' : 'Links' }}
            </div>
            @error('category')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @else
        <div>
            <label for="category" class="block text-sm font-semibold text-slate-700">Category</label>
            <select id="category" name="category" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                @foreach ($categories as $value => $label)
                    <option value="{{ $value }}" @selected(old('category', $mediaItem->category) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('category')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endif

    <div>
        <label for="status" class="block text-sm font-semibold text-slate-700">Status</label>
        <select id="status" name="status" required class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500">
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $mediaItem->status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-2">
        <label for="url" class="block text-sm font-semibold text-slate-700">
            @if ($isVideoLinkForm)
                Video Link URL
            @elseif ($isMediaLinkForm)
                Media Link URL
            @else
                URL
            @endif
        </label>
        <input
            id="url"
            name="url"
            type="url"
            value="{{ old('url', $mediaItem->url) }}"
            @required($isLinkOnlyForm)
            class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
            placeholder="{{ $isVideoLinkForm ? 'https://youtube.com/watch?v=...' : 'https://example.com/resource' }}"
        >
        <p class="mt-2 text-xs text-slate-500">
            @if ($isVideoLinkForm)
                Paste a YouTube, Vimeo, Wistia, or other external video URL.
            @elseif ($isMediaLinkForm)
                Paste an external article, research page, reference URL, or hosted media link.
            @else
                Use this for video links, research URLs, embedded resources, or external documents.
            @endif
        </p>
        @error('url')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    @unless ($isLinkOnlyForm)
        <div class="lg:col-span-2">
            <label for="media_file" class="block text-sm font-semibold text-slate-700">Upload file</label>
            <input
                id="media_file"
                name="media_file"
                type="file"
                class="mt-1 block w-full rounded-md border border-teal-100 bg-white text-sm text-slate-700 shadow-sm file:mr-4 file:border-0 file:bg-teal-50 file:px-4 file:py-2.5 file:text-sm file:font-bold file:text-teal-800 hover:file:bg-teal-100 focus:border-teal-500 focus:ring-teal-500"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.mp4,.mov,.avi,.webm,.mkv,.jpg,.jpeg,.png,.webp"
            >
            <p class="mt-2 text-xs text-slate-500">Upload documents or video files up to 50 MB. Supported examples: PDF, DOCX, PPTX, MP4, MOV, WEBM.</p>
            @if ($mediaItem->file_path)
                <div class="mt-3 rounded-md border border-teal-100 bg-teal-50 px-4 py-3 text-sm text-teal-900">
                    Current file:
                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($mediaItem->file_path) }}" target="_blank" rel="noreferrer" class="font-semibold underline decoration-teal-300 underline-offset-4">
                        {{ $mediaItem->file_name ?? basename($mediaItem->file_path) }}
                    </a>
                </div>
            @endif
            @error('media_file')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endunless

    @if ($isMediaLinkForm || old('category', $mediaItem->category) === 'links')
        <div class="lg:col-span-2">
            <label for="thumbnail_file" class="block text-sm font-semibold text-slate-700">Upload thumbnail</label>
            <input
                id="thumbnail_file"
                name="thumbnail_file"
                type="file"
                class="mt-1 block w-full rounded-md border border-teal-100 bg-white text-sm text-slate-700 shadow-sm file:mr-4 file:border-0 file:bg-teal-50 file:px-4 file:py-2.5 file:text-sm file:font-bold file:text-teal-800 hover:file:bg-teal-100 focus:border-teal-500 focus:ring-teal-500"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
            >
            <p class="mt-2 text-xs text-slate-500">Upload a JPG, PNG, or WEBP thumbnail up to 5 MB for this media link.</p>
            @if ($mediaItem->thumbnail_path)
                <div class="mt-3 flex items-center gap-3 rounded-md border border-teal-100 bg-teal-50 px-4 py-3 text-sm text-teal-900">
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($mediaItem->thumbnail_path) }}" alt="" class="size-14 rounded-md object-cover">
                    <div class="min-w-0">
                        <p class="font-semibold">Current thumbnail</p>
                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($mediaItem->thumbnail_path) }}" target="_blank" rel="noreferrer" class="block truncate underline decoration-teal-300 underline-offset-4">
                            {{ $mediaItem->thumbnail_name ?? basename($mediaItem->thumbnail_path) }}
                        </a>
                    </div>
                </div>
            @endif
            @error('thumbnail_file')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endif

    <div class="lg:col-span-2">
        <label for="description" class="block text-sm font-semibold text-slate-700">Description</label>
        <textarea
            id="description"
            name="description"
            rows="5"
            class="mt-1 block w-full rounded-md border-teal-100 text-slate-900 shadow-sm focus:border-teal-500 focus:ring-teal-500"
            placeholder="Short note about where this media item appears or how it should be used."
        >{{ old('description', $mediaItem->description) }}</textarea>
        @error('description')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
    <a href="{{ route('admin.media.index') }}" class="inline-flex items-center justify-center rounded-md border border-teal-200 bg-white px-5 py-2.5 text-sm font-bold text-teal-800 shadow-sm transition hover:bg-teal-50">
        Cancel
    </a>
    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-teal-400 px-5 py-2.5 text-sm font-bold text-[#031a19] shadow-[0_14px_28px_rgba(45,212,191,0.22)] transition hover:bg-teal-300">
        {{ $submitLabel }}
    </button>
</div>
