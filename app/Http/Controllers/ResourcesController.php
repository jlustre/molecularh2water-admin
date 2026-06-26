<?php

namespace App\Http\Controllers;

use App\Models\MediaItem;
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

    public function open(MediaItem $mediaItem): RedirectResponse|BinaryFileResponse
    {
        if ($mediaItem->file_path) {
            abort_unless(Storage::disk('public')->exists($mediaItem->file_path), 404);

            return response()->file(Storage::disk('public')->path($mediaItem->file_path), [
                'Content-Type' => $mediaItem->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.($mediaItem->file_name ?: basename($mediaItem->file_path)).'"',
            ]);
        }

        abort_unless($mediaItem->url, 404);

        return redirect()->away($mediaItem->url);
    }

    public function thumbnail(MediaItem $mediaItem): BinaryFileResponse
    {
        abort_unless($mediaItem->thumbnail_path, 404);
        abort_unless(Storage::disk('public')->exists($mediaItem->thumbnail_path), 404);

        return response()->file(Storage::disk('public')->path($mediaItem->thumbnail_path), [
            'Content-Type' => $mediaItem->thumbnail_mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.($mediaItem->thumbnail_name ?: basename($mediaItem->thumbnail_path)).'"',
        ]);
    }
}
