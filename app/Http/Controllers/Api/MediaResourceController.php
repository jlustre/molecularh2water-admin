<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MediaItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class MediaResourceController extends Controller
{
    private const CATEGORIES = [
        'documents' => 'Documents',
        'videos' => 'Videos',
        'links' => 'Links',
        'images' => 'Images',
        'downloads' => 'Downloads',
        'embedded' => 'Embedded Resources',
    ];

    public function documents(): JsonResponse
    {
        return $this->resourcesFor('documents');
    }

    public function videos(): JsonResponse
    {
        return $this->resourcesFor('videos');
    }

    public function links(): JsonResponse
    {
        return $this->resourcesFor('links');
    }

    public function images(): JsonResponse
    {
        return $this->resourcesFor('images');
    }

    public function downloads(): JsonResponse
    {
        return $this->resourcesFor('downloads');
    }

    public function embedded(): JsonResponse
    {
        return $this->resourcesFor('embedded');
    }

    private function resourcesFor(string $category): JsonResponse
    {
        $resources = MediaItem::query()
            ->where('status', 'published')
            ->where('category', $category)
            ->latest()
            ->get()
            ->map(fn (MediaItem $mediaItem) => $this->formatResource($mediaItem));

        return response()->json([
            'category' => $category,
            'category_label' => self::CATEGORIES[$category],
            'count' => $resources->count(),
            'data' => $resources,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatResource(MediaItem $mediaItem): array
    {
        $openResourceUrl = $this->absoluteUrl(route('media.open', $mediaItem, false));
        $fileUrl = $mediaItem->file_path ? $openResourceUrl : null;

        $thumbnailUrl = $mediaItem->thumbnail_path
            ? $this->absoluteUrl(route('media.thumbnail', $mediaItem, false))
            : $mediaItem->videoThumbnailUrl();

        $resourceUrl = $fileUrl ?: $mediaItem->url;

        return [
            'id' => $mediaItem->id,
            'title' => $mediaItem->title,
            'description' => $mediaItem->description,
            'category' => $mediaItem->category,
            'category_label' => self::CATEGORIES[$mediaItem->category] ?? ucfirst($mediaItem->category),
            'shareable_link' => $this->absoluteUrl(route('media.show', $mediaItem, false)),
            'open_resource_link' => $openResourceUrl,
            'url' => $mediaItem->url,
            'file_url' => $fileUrl,
            'resource_url' => $resourceUrl,
            'thumbnail_url' => $thumbnailUrl,
            'thumbnail_name' => $mediaItem->thumbnail_name,
            'thumbnail_size' => $mediaItem->thumbnail_size,
            'thumbnail_mime_type' => $mediaItem->thumbnail_mime_type,
            'file_name' => $mediaItem->file_name,
            'file_size' => $mediaItem->file_size,
            'mime_type' => $mediaItem->mime_type,
            'is_pdf' => $mediaItem->isPdf(),
            'is_video' => $mediaItem->isVideo(),
            'created_at' => $mediaItem->created_at?->toISOString(),
            'updated_at' => $mediaItem->updated_at?->toISOString(),
        ];
    }

    private function absoluteUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
    }
}
