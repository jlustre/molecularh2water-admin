<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MediaController extends Controller
{
    private const CATEGORIES = [
        'documents' => 'Documents',
        'videos' => 'Videos',
        'links' => 'Links',
        'images' => 'Images',
        'downloads' => 'Downloads',
        'embedded' => 'Embedded Resources',
    ];

    private const STATUSES = [
        'draft' => 'Draft',
        'review' => 'Review',
        'published' => 'Published',
        'archived' => 'Archived',
    ];

    public function index(Request $request): View
    {
        $query = MediaItem::query()->latest();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category') && array_key_exists($request->category, self::CATEGORIES)) {
            $query->where('category', $request->category);
        }

        $mediaItems = $query->paginate(10)->withQueryString();

        return view('admin.media.index', [
            'categories' => self::CATEGORIES,
            'statuses' => self::STATUSES,
            'mediaItems' => $mediaItems,
            'categoryCounts' => MediaItem::query()
                ->selectRaw('category, count(*) as aggregate')
                ->groupBy('category')
                ->pluck('aggregate', 'category'),
            'storageBytes' => MediaItem::query()->sum('file_size'),
        ]);
    }

    public function create(Request $request): View
    {
        $isVideoLinkForm = $request->query('mode') === 'video-link';
        $isMediaLinkForm = $request->query('mode') === 'media-link';
        $category = array_key_exists($request->category, self::CATEGORIES)
            ? $request->category
            : 'documents';

        if ($isVideoLinkForm) {
            $category = 'videos';
        }

        if ($isMediaLinkForm) {
            $category = 'links';
        }

        return view('admin.media.create', [
            'categories' => self::CATEGORIES,
            'statuses' => self::STATUSES,
            'mediaItem' => new MediaItem(['status' => 'draft', 'category' => $category]),
            'isVideoLinkForm' => $isVideoLinkForm,
            'isMediaLinkForm' => $isMediaLinkForm,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        MediaItem::create($this->mediaAttributes($request));

        return redirect()
            ->route('admin.media.index')
            ->with('status', 'Media item created.');
    }

    public function updateSeeder(): RedirectResponse
    {
        $mediaItems = MediaItem::query()
            ->orderBy('id')
            ->get([
                'id',
                'title',
                'category',
                'status',
                'url',
                'file_path',
                'description',
                'file_name',
                'file_size',
                'mime_type',
                'thumbnail_path',
                'thumbnail_name',
                'thumbnail_size',
                'thumbnail_mime_type',
                'created_at',
                'updated_at',
            ])
            ->map(fn (MediaItem $mediaItem) => [
                'id' => $mediaItem->id,
                'title' => $mediaItem->title,
                'category' => $mediaItem->category,
                'status' => $mediaItem->status,
                'url' => $mediaItem->url,
                'file_path' => $mediaItem->file_path,
                'description' => $mediaItem->description,
                'file_name' => $mediaItem->file_name,
                'file_size' => $mediaItem->file_size,
                'mime_type' => $mediaItem->mime_type,
                'thumbnail_path' => $mediaItem->thumbnail_path,
                'thumbnail_name' => $mediaItem->thumbnail_name,
                'thumbnail_size' => $mediaItem->thumbnail_size,
                'thumbnail_mime_type' => $mediaItem->thumbnail_mime_type,
                'created_at' => $mediaItem->created_at?->toDateTimeString(),
                'updated_at' => $mediaItem->updated_at?->toDateTimeString(),
            ])
            ->all();

        $exportedItems = var_export($mediaItems, true);
        $generatedAt = now()->toDateTimeString();
        $path = database_path('seeders/MediaItemsSeeder.php');

        File::put($path, <<<PHP
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MediaItemsSeeder extends Seeder
{
    /**
     * Seed the media library from the admin export generated at {$generatedAt}.
     */
    public function run(): void
    {
        \$mediaItems = {$exportedItems};

        foreach (\$mediaItems as \$mediaItem) {
            DB::table('media_items')->updateOrInsert(
                ['id' => \$mediaItem['id']],
                \$mediaItem
            );
        }
    }
}
PHP);

        return redirect()
            ->route('admin.media.index')
            ->with('status', 'MediaItemsSeeder.php updated with '.count($mediaItems).' media item'.(count($mediaItems) === 1 ? '' : 's').'.');
    }

    public function edit(MediaItem $medium): View
    {
        return view('admin.media.edit', [
            'categories' => self::CATEGORIES,
            'statuses' => self::STATUSES,
            'mediaItem' => $medium,
        ]);
    }

    public function viewPdf(MediaItem $medium): BinaryFileResponse
    {
        abort_unless($medium->file_path && $medium->isPdf(), 404);

        $path = Storage::disk('public')->path($medium->file_path);

        abort_unless(file_exists($path), 404);

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.($medium->file_name ?? 'media.pdf').'"',
        ]);
    }

    public function update(Request $request, MediaItem $medium): RedirectResponse
    {
        $attributes = $this->mediaAttributes($request, $medium);

        if ($request->hasFile('media_file') && $medium->file_path) {
            Storage::disk('public')->delete($medium->file_path);
        }

        if ($request->hasFile('thumbnail_file') && $medium->thumbnail_path) {
            Storage::disk('public')->delete($medium->thumbnail_path);
        }

        $medium->update($attributes);

        return redirect()
            ->route('admin.media.index')
            ->with('status', 'Media item updated.');
    }

    public function destroy(MediaItem $medium): RedirectResponse
    {
        if ($medium->file_path) {
            Storage::disk('public')->delete($medium->file_path);
        }

        if ($medium->thumbnail_path) {
            Storage::disk('public')->delete($medium->thumbnail_path);
        }

        $medium->delete();

        return redirect()
            ->route('admin.media.index')
            ->with('status', 'Media item deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function mediaAttributes(Request $request, ?MediaItem $mediaItem = null): array
    {
        $attributes = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(self::CATEGORIES))],
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(self::STATUSES))],
            'url' => [
                Rule::requiredIf(fn () => $request->category === 'links' || ($request->category === 'videos' && ! $request->hasFile('media_file') && ! $mediaItem?->file_path)),
                'nullable',
                'url',
                'max:2048',
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'media_file' => [
                Rule::requiredIf(fn () => in_array($request->category, ['documents', 'downloads'], true) && ! $mediaItem?->file_path),
                'nullable',
                'file',
                'max:51200',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,mp4,mov,avi,webm,mkv,jpg,jpeg,png,webp',
            ],
            'thumbnail_file' => [
                'nullable',
                'file',
                'max:5120',
                'mimes:jpg,jpeg,png,webp',
            ],
        ]);

        unset($attributes['media_file']);
        unset($attributes['thumbnail_file']);

        if ($request->hasFile('media_file')) {
            $file = $request->file('media_file');

            $attributes['file_path'] = $file->store('media/'.$attributes['category'], 'public');
            $attributes['file_name'] = $file->getClientOriginalName();
            $attributes['file_size'] = $file->getSize();
            $attributes['mime_type'] = $file->getMimeType();
        }

        if ($request->hasFile('thumbnail_file')) {
            $thumbnail = $request->file('thumbnail_file');

            $attributes['thumbnail_path'] = $thumbnail->store('media/thumbnails', 'public');
            $attributes['thumbnail_name'] = $thumbnail->getClientOriginalName();
            $attributes['thumbnail_size'] = $thumbnail->getSize();
            $attributes['thumbnail_mime_type'] = $thumbnail->getMimeType();
        }

        return $attributes;
    }
}
