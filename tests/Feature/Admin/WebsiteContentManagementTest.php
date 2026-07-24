<?php

use App\Services\SettingsService;

it('allows an admin to manage website content', function () {
    $user = superAdminUser();
    $settings = app(SettingsService::class);

    $this->actingAs($user)
        ->get(route('admin.website-content.edit'))
        ->assertOk()
        ->assertSee('Website Content')
        ->assertSee('Public email')
        ->assertSee('Facebook URL')
        ->assertSee('info@molecularh2water.com');

    $this->actingAs($user)
        ->put(route('admin.website-content.update'), [
            'site_company_name' => 'H2 Systems Public',
            'site_support_email' => 'hello@example.com',
            'site_support_phone' => '(555) 111-2222',
            'site_location' => 'Austin, TX',
            'site_facebook_url' => 'https://www.facebook.com/h2systems',
            'site_youtube_url' => 'https://www.youtube.com/@h2systems',
            'site_consumers_guide_url' => 'https://example.com/guide',
        ])
        ->assertRedirect(route('admin.website-content.edit'));

    expect($settings->get('site.support_email'))->toBe('hello@example.com')
        ->and($settings->get('site.support_phone'))->toBe('(555) 111-2222')
        ->and($settings->get('site.location'))->toBe('Austin, TX')
        ->and($settings->get('site.facebook_url'))->toBe('https://www.facebook.com/h2systems');
});

it('exposes public website content through the api', function () {
    $settings = app(SettingsService::class);
    $settings->set('site.support_email', 'api@example.com');
    $settings->set('site.support_phone', '(555) 999-0000');
    $settings->set('site.location', 'Seattle, WA');

    $this->getJson('/api/site-settings')
        ->assertOk()
        ->assertJsonPath('data.email', 'api@example.com')
        ->assertJsonPath('data.phone', '(555) 999-0000')
        ->assertJsonPath('data.phone_tel', '5559990000')
        ->assertJsonPath('data.location', 'Seattle, WA');
});
