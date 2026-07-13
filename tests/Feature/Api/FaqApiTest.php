<?php

use App\Models\Faq;

it('returns published faqs ordered by sort_order', function () {
    Faq::create([
        'question' => 'Second question?',
        'answer' => 'Second answer.',
        'status' => 'published',
        'sort_order' => 2,
    ]);

    Faq::create([
        'question' => 'First question?',
        'answer' => 'First answer.',
        'status' => 'published',
        'sort_order' => 1,
    ]);

    Faq::create([
        'question' => 'Draft question?',
        'answer' => 'This should not appear.',
        'status' => 'draft',
        'sort_order' => 0,
    ]);

    $this->getJson('/api/faqs')
        ->assertOk()
        ->assertJsonPath('count', 2)
        ->assertJsonPath('data.0.question', 'First question?')
        ->assertJsonPath('data.0.answer', 'First answer.')
        ->assertJsonPath('data.0.sort_order', 1)
        ->assertJsonPath('data.1.question', 'Second question?')
        ->assertJsonMissing(['question' => 'Draft question?']);
});
