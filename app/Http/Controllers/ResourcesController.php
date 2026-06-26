<?php

namespace App\Http\Controllers;

use App\Models\MediaItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResourcesController extends Controller
{
    private const CATEGORIES = [
        'documents' => 'Documents',
        'videos' => 'Videos',
        'links' => 'Links',
        'images' => 'Images',
        'downloads' => 'Downloads',
        'embedded' => 'Embedded Resources',
    ];

    public function index(Request $request): View
    {
        $query = MediaItem::query()
            ->where('status', 'published')
            ->latest();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%")
                    ->orWhere('file_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category') && array_key_exists($request->category, self::CATEGORIES)) {
            $query->where('category', $request->category);
        }

        return view('resources.index', [
            'categories' => self::CATEGORIES,
            'resources' => $query->paginate(12)->withQueryString(),
            'categoryCounts' => MediaItem::query()
                ->where('status', 'published')
                ->selectRaw('category, count(*) as aggregate')
                ->groupBy('category')
                ->pluck('aggregate', 'category'),
        ]);
    }

    public function show(MediaItem $mediaItem): View
    {
        return view('resources.show', [
            'categories' => self::CATEGORIES,
            'resource' => $mediaItem,
        ]);
    }

    public function open(int|string $mediaItem): RedirectResponse|BinaryFileResponse|JsonResponse
    {
        $requestedMediaId = (string) $mediaItem;
        $mediaItem = MediaItem::query()->find($requestedMediaId);

        if (! $mediaItem) {
            return response()->json([
                'message' => 'Media item not found.',
                'media_id' => $requestedMediaId,
            ], 404);
        }

        if ($mediaItem->file_path) {
            $path = $this->publicDiskPath($mediaItem->file_path);

            if ($path) {
                return response()->file($path, [
                    'Content-Type' => $mediaItem->mime_type ?: 'application/octet-stream',
                    'Content-Disposition' => 'inline; filename="'.($mediaItem->file_name ?: basename($mediaItem->file_path)).'"',
                ]);
            }

            if ($mediaItem->url) {
                return redirect()->away($mediaItem->url);
            }
        }

        if (! $mediaItem->url) {
            return response()->json([
                'message' => 'Media item has no accessible file or URL.',
                'media_id' => $mediaItem->id,
                'file_path' => $mediaItem->file_path,
                'checked_paths' => $mediaItem->file_path ? $this->candidatePublicPaths($mediaItem->file_path) : [],
            ], 404);
        }

        return redirect()->away($mediaItem->url);
    }

    public function thumbnail(MediaItem $mediaItem): BinaryFileResponse
    {
        abort_unless($mediaItem->thumbnail_path, 404);
        $path = $this->publicDiskPath($mediaItem->thumbnail_path);

        abort_unless($path, 404);

        return response()->file($path, [
            'Content-Type' => $mediaItem->thumbnail_mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.($mediaItem->thumbnail_name ?: basename($mediaItem->thumbnail_path)).'"',
        ]);
    }

    private function publicDiskPath(string $path): ?string
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->path($path);
        }

        $normalizedPath = ltrim($path, '/');
        $publicStoragePath = public_path('storage/'.$normalizedPath);

        if (file_exists($publicStoragePath)) {
            return $publicStoragePath;
        }

        if (str_starts_with($normalizedPath, 'storage/')) {
            $publicPath = public_path($normalizedPath);

            if (file_exists($publicPath)) {
                return $publicPath;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function candidatePublicPaths(string $path): array
    {
        $normalizedPath = ltrim($path, '/');
        $paths = [
            Storage::disk('public')->path($path),
            public_path('storage/'.$normalizedPath),
        ];

        if (str_starts_with($normalizedPath, 'storage/')) {
            $paths[] = public_path($normalizedPath);
        }

        return array_values(array_unique($paths));
    }
}
