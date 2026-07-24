<?php

use App\Models\BlogPost;

it('allows an admin to manage blog posts', function () {
    $user = superAdminUser();

    $this->actingAs($user)
        ->get(route('admin.blog.index'))
        ->assertOk()
        ->assertSee('Blog / Education')
        ->assertSee('Add Post');

    $this->actingAs($user)
        ->post(route('admin.blog.store'), [
            'title' => 'Hydrogen water basics',
            'slug' => '',
            'excerpt' => 'A short intro to molecular hydrogen.',
            'body' => 'Molecular hydrogen water may support antioxidant activity.',
            'status' => 'published',
            'published_at' => '2026-07-19T10:00',
            'sort_order' => 1,
        ])
        ->assertRedirect(route('admin.blog.index'));

    $post = BlogPost::query()->first();

    expect($post)->not->toBeNull();
    $this->assertDatabaseHas('blog_posts', [
        'title' => 'Hydrogen water basics',
        'slug' => 'hydrogen-water-basics',
        'status' => 'published',
        'sort_order' => 1,
        'author_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('admin.blog.index'))
        ->assertOk()
        ->assertSee('Hydrogen water basics');

    $this->actingAs($user)
        ->get(route('admin.blog.edit', $post))
        ->assertOk()
        ->assertSee('Edit Post')
        ->assertSee('Hydrogen water basics');

    $this->actingAs($user)
        ->put(route('admin.blog.update', $post), [
            'title' => 'Is hydrogen water safe?',
            'slug' => 'is-hydrogen-water-safe',
            'excerpt' => 'Safety overview.',
            'body' => 'Molecular hydrogen has a strong safety profile.',
            'status' => 'review',
            'published_at' => null,
            'sort_order' => 2,
        ])
        ->assertRedirect(route('admin.blog.index'));

    $this->assertDatabaseHas('blog_posts', [
        'id' => $post->id,
        'title' => 'Is hydrogen water safe?',
        'slug' => 'is-hydrogen-water-safe',
        'status' => 'review',
        'sort_order' => 2,
    ]);

    $this->actingAs($user)
        ->get(route('admin.blog.index', ['search' => 'safe']))
        ->assertOk()
        ->assertSee('Is hydrogen water safe?');

    $this->actingAs($user)
        ->get(route('admin.blog.index', ['status' => 'review']))
        ->assertOk()
        ->assertSee('Is hydrogen water safe?');

    $this->actingAs($user)
        ->delete(route('admin.blog.destroy', $post))
        ->assertRedirect(route('admin.blog.index'));

    $this->assertDatabaseMissing('blog_posts', [
        'id' => $post->id,
    ]);
});
