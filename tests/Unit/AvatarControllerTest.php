<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class);

test('avatar route serves files from the public disk', function () {
    Storage::fake('public');

    $path = UploadedFile::fake()
        ->image('avatar.png', 40, 40)
        ->store('avatars', 'public');

    $this->get(route('avatars.show', ['filename' => basename($path)]))
        ->assertOk()
        ->assertHeader('content-disposition', 'inline; filename="'.basename($path).'"');
});

test('avatar route returns not found for missing files', function () {
    Storage::fake('public');

    $this->get(route('avatars.show', ['filename' => 'missing.jpg']))
        ->assertNotFound();
});

test('avatar route rejects unsafe filenames', function () {
    $this->get('/avatars/../.env')
        ->assertNotFound();
});
