<?php

use App\Models\MediaItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

it('shows published admin media to authenticated users as read-only resources', function () {
    $user = User::factory()->create();

    $publishedMedia = MediaItem::create([
        'title' => 'Published Hydrogen Guide',
        'category' => 'documents',
        'status' => 'published',
        'description' => 'A guide from the admin media library.',
        'url' => 'https://example.com/published-guide.pdf',
    ]);

    MediaItem::create([
        'title' => 'Draft Internal Notes',
        'category' => 'documents',
        'status' => 'draft',
        'description' => 'This should not be visible to users.',
        'url' => 'https://example.com/draft-notes.pdf',
    ]);

    $this->actingAs($user)
        ->get(route('resources'))
        ->assertOk()
        ->assertSee('Resource Center')
        ->assertSee('Published Hydrogen Guide')
        ->assertSee('Open Link')
        ->assertSee('Share Link')
        ->assertSee(route('media.show', $publishedMedia), false)
        ->assertDontSee('Draft Internal Notes')
        ->assertDontSee('Edit')
        ->assertDontSee('Delete');
});

it('links to resources from the user dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Resources')
        ->assertSee(route('resources'));
});

it('allows guests to open a direct media share link', function () {
    $mediaItem = MediaItem::create([
        'title' => 'Public Hydrogen Video',
        'category' => 'videos',
        'status' => 'published',
        'description' => 'A video that can be shared outside the portal.',
        'url' => 'https://vimeo.com/public-hydrogen-video',
    ]);

    $this->get(route('media.show', $mediaItem))
        ->assertOk()
        ->assertSee('Public Hydrogen Video')
        ->assertSee('This public link can be opened without creating an account.')
        ->assertSee('Open Link')
        ->assertDontSee('Login required');
});

it('allows guests to open a resource through the public open endpoint', function () {
    Storage::fake('public');

    $path = UploadedFile::fake()
        ->create('hydrogen-guide.pdf', 128, 'application/pdf')
        ->store('media/documents', 'public');

    $mediaItem = MediaItem::create([
        'title' => 'Public Hydrogen Guide',
        'category' => 'documents',
        'status' => 'published',
        'description' => 'A guide that can be opened outside the portal.',
        'file_path' => $path,
        'file_name' => 'hydrogen-guide.pdf',
        'mime_type' => 'application/pdf',
    ]);

    $this->get("http://localhost:8000/media/{$mediaItem->id}/open")
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('allows guests to view an uploaded thumbnail through the public thumbnail endpoint', function () {
    Storage::fake('public');

    $path = UploadedFile::fake()
        ->create('hydrogen-thumbnail.jpg', 64, 'image/jpeg')
        ->store('media/thumbnails', 'public');

    $mediaItem = MediaItem::create([
        'title' => 'Public Hydrogen Link',
        'category' => 'links',
        'status' => 'published',
        'url' => 'https://example.com/hydrogen',
        'thumbnail_path' => $path,
        'thumbnail_name' => 'hydrogen-thumbnail.jpg',
        'thumbnail_mime_type' => 'image/jpeg',
    ]);

    $this->get("http://localhost:8000/media/{$mediaItem->id}/thumbnail")
        ->assertOk()
        ->assertHeader('content-type', 'image/jpeg');
});

it('opens files from the public storage path when the storage disk cannot find them', function () {
    $path = 'media/documents/public-storage-guide.pdf';
    $absolutePath = public_path('storage/'.$path);

    File::ensureDirectoryExists(dirname($absolutePath));
    File::put($absolutePath, '%PDF-1.4 test');

    $mediaItem = MediaItem::create([
        'title' => 'Public Storage Guide',
        'category' => 'documents',
        'status' => 'published',
        'file_path' => $path,
        'file_name' => 'public-storage-guide.pdf',
        'mime_type' => 'application/pdf',
    ]);

    try {
        $this->get("http://localhost:8000/media/{$mediaItem->id}/open")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    } finally {
        File::delete($absolutePath);
    }
});

it('opens files from the deployable seed media path when public storage is missing them', function () {
    Storage::fake('public');

    $path = 'media/documents/seed-media-guide.pdf';
    $absolutePath = database_path('seeders/'.$path);

    File::ensureDirectoryExists(dirname($absolutePath));
    File::put($absolutePath, '%PDF-1.4 test');

    $mediaItem = MediaItem::create([
        'title' => 'Seed Media Guide',
        'category' => 'documents',
        'status' => 'published',
        'file_path' => $path,
        'file_name' => 'seed-media-guide.pdf',
        'mime_type' => 'application/pdf',
    ]);

    try {
        $this->get("http://localhost:8000/media/{$mediaItem->id}/open")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    } finally {
        File::delete($absolutePath);
    }
});
it('falls back to the external url when a stored file is missing', function () {
    $mediaItem = MediaItem::create([
        'title' => 'Fallback Research Link',
        'category' => 'documents',
        'status' => 'published',
        'url' => 'https://example.com/fallback-research.pdf',
        'file_path' => 'media/documents/missing-file.pdf',
        'file_name' => 'missing-file.pdf',
        'mime_type' => 'application/pdf',
    ]);

    $this->get("http://localhost:8000/media/{$mediaItem->id}/open")
        ->assertRedirect('https://example.com/fallback-research.pdf');
});

it('returns a diagnostic response when opening a missing media item', function () {
    $this->getJson('http://localhost:8000/media/11/open')
        ->assertNotFound()
        ->assertJsonPath('message', 'Media item not found.')
        ->assertJsonPath('media_id', '11');
});

it('returns a diagnostic response when a media item has no accessible resource', function () {
    $mediaItem = MediaItem::create([
        'title' => 'Broken Upload',
        'category' => 'documents',
        'status' => 'published',
        'file_path' => 'media/documents/missing-upload.pdf',
        'file_name' => 'missing-upload.pdf',
        'mime_type' => 'application/pdf',
    ]);

    $this->getJson("http://localhost:8000/media/{$mediaItem->id}/open")
        ->assertNotFound()
        ->assertJsonPath('message', 'Media item has no accessible file or URL.')
        ->assertJsonPath('media_id', $mediaItem->id)
        ->assertJsonPath('file_path', 'media/documents/missing-upload.pdf');
});
