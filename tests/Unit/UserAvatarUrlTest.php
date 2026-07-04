<?php

use App\Models\User;
use Illuminate\Support\Carbon;

uses(Tests\TestCase::class);

test('avatar url uses application route and cache busting version from updated_at', function () {
    $user = new User(['avatar_path' => 'avatars/photo.jpg']);
    $user->updated_at = Carbon::createFromTimestamp(1_700_000_000);

    expect($user->avatarUrl())
        ->toBe(route('avatars.show', ['filename' => 'photo.jpg']).'?v=1700000000')
        ->toContain('/avatars/photo.jpg')
        ->toContain('?v=1700000000');
});

test('avatar url is null when no avatar path is set', function () {
    $user = new User(['avatar_path' => null]);

    expect($user->avatarUrl())->toBeNull();
});

test('avatar url uses only the filename from avatar_path', function () {
    $user = new User(['avatar_path' => 'avatars/nested/should-not-matter/photo.webp']);
    $user->updated_at = Carbon::createFromTimestamp(1_700_000_001);

    expect($user->avatarUrl())
        ->toContain('/avatars/photo.webp')
        ->not->toContain('nested');
});
