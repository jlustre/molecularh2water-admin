<?php

use App\Models\MediaItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

it('allows an admin to manage media items', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.media.index'))
        ->assertOk()
        ->assertSee('Media Library')
        ->assertSee('Add Media')
        ->assertSee('Update Seeder');

    $this->actingAs($user)
        ->post(route('admin.media.store'), [
            'title' => 'Hydrogen Water Guide',
            'category' => 'documents',
            'status' => 'published',
            'description' => 'A public education document.',
            'media_file' => UploadedFile::fake()->create('hydrogen-water-guide.pdf', 128, 'application/pdf'),
        ])
        ->assertRedirect(route('admin.media.index'));

    $mediaItem = MediaItem::first();

    expect($mediaItem)->not->toBeNull();
    $this->assertDatabaseHas('media_items', [
        'title' => 'Hydrogen Water Guide',
        'category' => 'documents',
        'status' => 'published',
        'file_name' => 'hydrogen-water-guide.pdf',
    ]);

    Storage::disk('public')->assertExists($mediaItem->file_path);

    $this->actingAs($user)
        ->get(route('admin.media.index'))
        ->assertOk()
        ->assertSee('View PDF');

    $this->actingAs($user)
        ->get(route('admin.media.view-pdf', $mediaItem))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs($user)
        ->get(route('admin.media.edit', $mediaItem))
        ->assertOk()
        ->assertSee('Edit media item')
        ->assertSee('Hydrogen Water Guide');

    $originalPath = $mediaItem->file_path;

    $this->actingAs($user)
        ->put(route('admin.media.update', $mediaItem), [
            'title' => 'Updated Product Demo',
            'category' => 'videos',
            'status' => 'review',
            'description' => 'Updated video notes.',
            'media_file' => UploadedFile::fake()->create('product-demo.mp4', 256, 'video/mp4'),
        ])
        ->assertRedirect(route('admin.media.index'));

    $this->assertDatabaseHas('media_items', [
        'id' => $mediaItem->id,
        'title' => 'Updated Product Demo',
        'category' => 'videos',
        'status' => 'review',
        'file_name' => 'product-demo.mp4',
    ]);

    Storage::disk('public')->assertMissing($originalPath);
    Storage::disk('public')->assertExists($mediaItem->fresh()->file_path);

    $this->actingAs($user)
        ->post(route('admin.media.store'), [
            'title' => 'Hydrogen Product Video Link',
            'category' => 'videos',
            'status' => 'published',
            'url' => 'https://vimeo.com/example-video',
            'description' => 'External product video.',
        ])
        ->assertRedirect(route('admin.media.index'));

    $this->assertDatabaseHas('media_items', [
        'title' => 'Hydrogen Product Video Link',
        'category' => 'videos',
        'url' => 'https://vimeo.com/example-video',
    ]);

    $this->actingAs($user)
        ->get(route('admin.media.index'))
        ->assertOk()
        ->assertSee('Open Video')
        ->assertSee('Share')
        ->assertSee(route('media.show', MediaItem::where('title', 'Hydrogen Product Video Link')->first()), false);

    $storedPath = $mediaItem->fresh()->file_path;

    $this->actingAs($user)
        ->delete(route('admin.media.destroy', $mediaItem))
        ->assertRedirect(route('admin.media.index'));

    $this->assertDatabaseMissing('media_items', [
        'id' => $mediaItem->id,
    ]);

    Storage::disk('public')->assertMissing($storedPath);
});

it('shows a link-only form from the add video link action', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.media.create', ['category' => 'videos', 'mode' => 'video-link']))
        ->assertOk()
        ->assertSee('Add video link')
        ->assertSee('Video Link URL')
        ->assertSee('Create Video Link')
        ->assertSee('name="category" value="videos"', false)
        ->assertDontSee('Upload file')
        ->assertDontSee('Upload documents or video files up to 50 MB');
});

it('shows a link-only form from the add media link action', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.media.create', ['category' => 'links', 'mode' => 'media-link']))
        ->assertOk()
        ->assertSee('Add media link')
        ->assertSee('Media Link URL')
        ->assertSee('Upload thumbnail')
        ->assertSee('Create Media Link')
        ->assertSee('name="category" value="links"', false)
        ->assertDontSee('Upload file')
        ->assertDontSee('Upload documents or video files up to 50 MB');
});

it('stores a thumbnail when creating a media link', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.media.store'), [
            'title' => 'Hydrogen Research Link',
            'category' => 'links',
            'status' => 'published',
            'url' => 'https://example.com/hydrogen-research',
            'description' => 'External research page.',
            'thumbnail_file' => UploadedFile::fake()->create('research-thumbnail.jpg', 64, 'image/jpeg'),
        ])
        ->assertRedirect(route('admin.media.index'));

    $mediaItem = MediaItem::where('title', 'Hydrogen Research Link')->first();

    expect($mediaItem)->not->toBeNull();
    expect($mediaItem->thumbnail_path)->not->toBeNull();
    expect($mediaItem->thumbnail_name)->toBe('research-thumbnail.jpg');

    Storage::disk('public')->assertExists($mediaItem->thumbnail_path);
});

it('exports current media records into a media items seeder', function () {
    $user = User::factory()->create();

    $mediaItem = MediaItem::create([
        'title' => 'Seeded Hydrogen Guide',
        'category' => 'documents',
        'status' => 'published',
        'description' => 'Stored in the exported seeder.',
        'url' => 'https://example.com/seeded-guide',
        'file_path' => 'media/documents/seeded-guide.pdf',
        'file_name' => 'seeded-guide.pdf',
        'file_size' => 12345,
        'mime_type' => 'application/pdf',
        'thumbnail_path' => 'media/thumbnails/seeded-guide.jpg',
        'thumbnail_name' => 'seeded-guide.jpg',
        'thumbnail_size' => 2345,
        'thumbnail_mime_type' => 'image/jpeg',
    ]);

    File::shouldReceive('put')
        ->once()
        ->withArgs(function (string $path, string $contents) use ($mediaItem) {
            expect($path)->toBe(database_path('seeders/MediaItemsSeeder.php'));

            return str_contains($contents, 'class MediaItemsSeeder')
                && str_contains($contents, 'use Illuminate\Support\Facades\Storage;')
                && str_contains($contents, '$this->seedMediaFiles($mediaItems);')
                && str_contains($contents, "DB::table('media_items')->updateOrInsert")
                && str_contains($contents, 'private function seedMediaFiles(array $mediaItems): void')
                && str_contains($contents, "'id' => {$mediaItem->id}")
                && str_contains($contents, "'title' => 'Seeded Hydrogen Guide'")
                && str_contains($contents, "'file_path' => 'media/documents/seeded-guide.pdf'")
                && str_contains($contents, "'thumbnail_path' => 'media/thumbnails/seeded-guide.jpg'");
        })
        ->andReturn(1);

    $this->actingAs($user)
        ->post(route('admin.media.update-seeder'))
        ->assertRedirect(route('admin.media.index'))
        ->assertSessionHas('status', 'MediaItemsSeeder.php updated with 1 media item.');
});
