<?php

use App\Models\MediaItem;
use App\Models\User;

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
