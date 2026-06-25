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
        $fileUrl = $mediaItem->file_path
            ? url(Storage::disk('public')->url($mediaItem->file_path))
            : null;

        return [
            'id' => $mediaItem->id,
            'title' => $mediaItem->title,
            'description' => $mediaItem->description,
            'category' => $mediaItem->category,
            'category_label' => self::CATEGORIES[$mediaItem->category] ?? ucfirst($mediaItem->category),
            'shareable_link' => route('media.show', $mediaItem),
            'url' => $mediaItem->url,
            'file_url' => $fileUrl,
            'resource_url' => $fileUrl ?: $mediaItem->url,
            'file_name' => $mediaItem->file_name,
            'file_size' => $mediaItem->file_size,
            'mime_type' => $mediaItem->mime_type,
            'is_pdf' => $mediaItem->isPdf(),
            'is_video' => $mediaItem->isVideo(),
            'created_at' => $mediaItem->created_at?->toISOString(),
            'updated_at' => $mediaItem->updated_at?->toISOString(),
        ];
    }
}
