<?php

namespace App\Http\Controllers;

use App\Models\MediaItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

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

    public function open(MediaItem $mediaItem): RedirectResponse
    {
        $resourceUrl = $mediaItem->file_path
            ? $this->absoluteUrl(Storage::disk('public')->url($mediaItem->file_path))
            : $mediaItem->url;

        abort_unless($resourceUrl, 404);

        return redirect()->away($resourceUrl);
    }

    private function absoluteUrl(string $path): string
    {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return rtrim(request()->getSchemeAndHttpHost(), '/').'/'.ltrim($path, '/');
    }
}
