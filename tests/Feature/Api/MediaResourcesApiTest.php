<?php

use App\Models\MediaItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('returns published document resources with shareable links and file details', function () {
    Storage::fake('public');

    $path = UploadedFile::fake()
        ->create('hydrogen-guide.pdf', 128, 'application/pdf')
        ->store('media/documents', 'public');

    $publishedDocument = MediaItem::create([
        'title' => 'Hydrogen Guide',
        'category' => 'documents',
        'status' => 'published',
        'description' => 'A public document resource.',
        'file_path' => $path,
        'file_name' => 'hydrogen-guide.pdf',
        'file_size' => 131072,
        'mime_type' => 'application/pdf',
    ]);

    MediaItem::create([
        'title' => 'Draft Guide',
        'category' => 'documents',
        'status' => 'draft',
        'description' => 'This should not appear.',
        'url' => 'https://example.com/draft-guide.pdf',
    ]);

    MediaItem::create([
        'title' => 'Published Video',
        'category' => 'videos',
        'status' => 'published',
        'description' => 'This belongs in the video endpoint.',
        'url' => 'https://vimeo.com/published-video',
    ]);

    $this->getJson('http://localhost:8000/api/resources/documents')
        ->assertOk()
        ->assertJsonPath('category', 'documents')
        ->assertJsonPath('category_label', 'Documents')
        ->assertJsonPath('count', 1)
        ->assertJsonPath('data.0.title', 'Hydrogen Guide')
        ->assertJsonPath('data.0.description', 'A public document resource.')
        ->assertJsonPath('data.0.shareable_link', "http://localhost:8000/media/{$publishedDocument->id}")
        ->assertJsonPath('data.0.open_resource_link', "http://localhost:8000/media/{$publishedDocument->id}/open")
        ->assertJsonPath('data.0.file_url', 'http://localhost:8000'.Storage::disk('public')->url($path))
        ->assertJsonPath('data.0.resource_url', 'http://localhost:8000'.Storage::disk('public')->url($path))
        ->assertJsonPath('data.0.is_pdf', true)
        ->assertJsonPath('data.0.is_video', false)
        ->assertJsonMissing(['title' => 'Draft Guide'])
        ->assertJsonMissing(['title' => 'Published Video']);
});

it('returns published video resources from the video endpoint', function () {
    $video = MediaItem::create([
        'title' => 'Hydrogen Product Replay',
        'category' => 'videos',
        'status' => 'published',
        'description' => 'A public video resource.',
        'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    ]);

    $this->getJson('/api/resources/videos')
        ->assertOk()
        ->assertJsonPath('category', 'videos')
        ->assertJsonPath('category_label', 'Videos')
        ->assertJsonPath('count', 1)
        ->assertJsonPath('data.0.title', 'Hydrogen Product Replay')
        ->assertJsonPath('data.0.url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
        ->assertJsonPath('data.0.resource_url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
        ->assertJsonPath('data.0.thumbnail_url', 'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg')
        ->assertJsonPath('data.0.shareable_link', route('media.show', $video))
        ->assertJsonPath('data.0.is_video', true);
});

it('returns vimeo source thumbnails for video resources', function () {
    MediaItem::create([
        'title' => 'Hydrogen Vimeo Replay',
        'category' => 'videos',
        'status' => 'published',
        'description' => 'A Vimeo video resource.',
        'url' => 'https://vimeo.com/123456789',
    ]);

    $this->getJson('/api/resources/videos')
        ->assertOk()
        ->assertJsonPath('data.0.thumbnail_url', 'https://vumbnail.com/123456789.jpg');
});

it('returns uploaded thumbnails for media link resources', function () {
    Storage::fake('public');

    $thumbnailPath = UploadedFile::fake()
        ->create('research-thumbnail.jpg', 64, 'image/jpeg')
        ->store('media/thumbnails', 'public');

    MediaItem::create([
        'title' => 'Hydrogen Research Link',
        'category' => 'links',
        'status' => 'published',
        'description' => 'A public media link resource.',
        'url' => 'https://example.com/hydrogen-research',
        'thumbnail_path' => $thumbnailPath,
        'thumbnail_name' => 'research-thumbnail.jpg',
        'thumbnail_size' => 65536,
        'thumbnail_mime_type' => 'image/jpeg',
    ]);

    $this->getJson('http://localhost:8000/api/resources/links')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Hydrogen Research Link')
        ->assertJsonPath('data.0.thumbnail_url', 'http://localhost:8000'.Storage::disk('public')->url($thumbnailPath))
        ->assertJsonPath('data.0.thumbnail_name', 'research-thumbnail.jpg')
        ->assertJsonPath('data.0.thumbnail_mime_type', 'image/jpeg');
});

it('exposes separate resource endpoints for every media category', function (string $category) {
    $this->getJson("/api/resources/{$category}")
        ->assertOk()
        ->assertJsonPath('category', $category)
        ->assertJsonPath('count', 0)
        ->assertJsonPath('data', []);
})->with([
    'documents',
    'videos',
    'links',
    'images',
    'downloads',
    'embedded',
]);
