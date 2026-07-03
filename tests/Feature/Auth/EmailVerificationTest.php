<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

function verificationUrl(User $user, ?string $email = null, bool $absolute = false): string
{
    $relativeUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($email ?? $user->email)],
        absolute: $absolute,
    );

    return $absolute ? $relativeUrl : rtrim((string) config('app.url'), '/').$relativeUrl;
}

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertStatus(200);
});

test('email can be verified while authenticated', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $response = $this->actingAs($user)->get(verificationUrl($user));

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});

test('guest can verify email from signed link without logging in first', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $response = $this->get(verificationUrl($user));

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});

test('verification link works when a different user is logged in', function () {
    $user = User::factory()->unverified()->create();
    $other = User::factory()->create();

    Event::fake();

    $response = $this->actingAs($other)->get(verificationUrl($user));

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});

test('verification link remains valid across request hosts', function () {
    $user = User::factory()->unverified()->create();

    config(['app.url' => 'http://molecularh2water-admin.test']);

    Notification::fake();
    $user->sendEmailVerificationNotification();

    $verificationUrl = null;
    Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user, &$verificationUrl) {
        $verificationUrl = $notification->toMail($user)->actionUrl;

        return str_starts_with($verificationUrl, 'http://molecularh2water-admin.test/verify-email/');
    });

    $path = parse_url($verificationUrl, PHP_URL_PATH).'?'.parse_url($verificationUrl, PHP_URL_QUERY);

    $response = $this->get('http://localhost:8000'.$path);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});

test('already verified users are redirected without error', function () {
    $user = User::factory()->create();

    $response = $this->get(verificationUrl($user));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $this->get(verificationUrl($user, 'wrong-email'))
        ->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('email is not verified with invalid signature', function () {
    $user = User::factory()->unverified()->create();

    $this->get('/verify-email/'.$user->id.'/'.sha1($user->email).'?expires='.now()->addHour()->getTimestamp().'&signature=invalid')
        ->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('legacy absolute signatures still verify when the host matches', function () {
    $user = User::factory()->unverified()->create();

    $absoluteUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
        absolute: true,
    );

    $response = $this->get($absoluteUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});
