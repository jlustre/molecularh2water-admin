<?php

use App\Models\Faq;
use App\Models\User;

it('allows an admin to manage faqs', function () {
    $user = superAdminUser();

    $this->actingAs($user)
        ->get(route('admin.faqs.index'))
        ->assertOk()
        ->assertSee('FAQ Library')
        ->assertSee('Add FAQ');

    $this->actingAs($user)
        ->post(route('admin.faqs.store'), [
            'question' => 'What is hydrogen water?',
            'answer' => 'Hydrogen water contains dissolved molecular hydrogen gas.',
            'status' => 'published',
            'sort_order' => 1,
        ])
        ->assertRedirect(route('admin.faqs.index'));

    $faq = Faq::query()->first();

    expect($faq)->not->toBeNull();
    $this->assertDatabaseHas('faqs', [
        'question' => 'What is hydrogen water?',
        'status' => 'published',
        'sort_order' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('admin.faqs.index'))
        ->assertOk()
        ->assertSee('What is hydrogen water?');

    $this->actingAs($user)
        ->get(route('admin.faqs.edit', $faq))
        ->assertOk()
        ->assertSee('Edit FAQ')
        ->assertSee('What is hydrogen water?');

    $this->actingAs($user)
        ->put(route('admin.faqs.update', $faq), [
            'question' => 'Is hydrogen water safe?',
            'answer' => 'Molecular hydrogen has a strong safety profile.',
            'status' => 'review',
            'sort_order' => 2,
        ])
        ->assertRedirect(route('admin.faqs.index'));

    $this->assertDatabaseHas('faqs', [
        'id' => $faq->id,
        'question' => 'Is hydrogen water safe?',
        'status' => 'review',
        'sort_order' => 2,
    ]);

    $this->actingAs($user)
        ->get(route('admin.faqs.index', ['search' => 'safe']))
        ->assertOk()
        ->assertSee('Is hydrogen water safe?');

    $this->actingAs($user)
        ->get(route('admin.faqs.index', ['status' => 'review']))
        ->assertOk()
        ->assertSee('Is hydrogen water safe?');

    $this->actingAs($user)
        ->delete(route('admin.faqs.destroy', $faq))
        ->assertRedirect(route('admin.faqs.index'));

    $this->assertDatabaseMissing('faqs', [
        'id' => $faq->id,
    ]);
});

it('seeds the twelve published faqs', function () {
    $this->seed(\Database\Seeders\FaqsSeeder::class);

    expect(Faq::query()->count())->toBe(12);
    expect(Faq::query()->published()->count())->toBe(12);
    expect(Faq::query()->ordered()->value('question'))->toBe('What is hydrogen water?');
});
