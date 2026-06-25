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

    $this->getJson('/api/resources/documents')
        ->assertOk()
        ->assertJsonPath('category', 'documents')
        ->assertJsonPath('category_label', 'Documents')
        ->assertJsonPath('count', 1)
        ->assertJsonPath('data.0.title', 'Hydrogen Guide')
        ->assertJsonPath('data.0.description', 'A public document resource.')
        ->assertJsonPath('data.0.shareable_link', route('media.show', $publishedDocument))
        ->assertJsonPath('data.0.file_url', url(Storage::disk('public')->url($path)))
        ->assertJsonPath('data.0.resource_url', url(Storage::disk('public')->url($path)))
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
        'url' => 'https://vimeo.com/hydrogen-product-replay',
    ]);

    $this->getJson('/api/resources/videos')
        ->assertOk()
        ->assertJsonPath('category', 'videos')
        ->assertJsonPath('category_label', 'Videos')
        ->assertJsonPath('count', 1)
        ->assertJsonPath('data.0.title', 'Hydrogen Product Replay')
        ->assertJsonPath('data.0.url', 'https://vimeo.com/hydrogen-product-replay')
        ->assertJsonPath('data.0.resource_url', 'https://vimeo.com/hydrogen-product-replay')
        ->assertJsonPath('data.0.shareable_link', route('media.show', $video))
        ->assertJsonPath('data.0.is_video', true);
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
