<?php

use App\Services\SettingsService;

it('allows an admin to view and update settings', function () {
    $user = superAdminUser();
    $settings = app(SettingsService::class);

    $settings->set('portal.online_demo_link', 'https://zoom.us/j/existing');

    $this->actingAs($user)
        ->get(route('admin.settings'))
        ->assertOk()
        ->assertSee('Settings')
        ->assertSee('Branding')
        ->assertSee('Contact')
        ->assertSee('Portal')
        ->assertSee('Notifications')
        ->assertSee('Appearance')
        ->assertSee('Molecular H2 Water')
        ->assertSee('https://zoom.us/j/existing');

    $this->actingAs($user)
        ->put(route('admin.settings.update'), [
            'site_company_name' => 'H2 Systems',
            'site_support_email' => 'support@example.com',
            'site_support_phone' => '555-0100',
            'portal_online_demo_link' => 'https://zoom.us/j/updated-demo',
            'notifications_from_name' => 'H2 Support',
            'notifications_from_email' => 'noreply@example.com',
            'sidebar_design' => 'design2',
        ])
        ->assertRedirect(route('admin.settings'));

    expect($settings->get('site.company_name'))->toBe('H2 Systems');
    expect($settings->get('site.support_email'))->toBe('support@example.com');
    expect($settings->get('site.support_phone'))->toBe('555-0100');
    expect($settings->get('portal.online_demo_link'))->toBe('https://zoom.us/j/updated-demo');
    expect($settings->get('notifications.from_name'))->toBe('H2 Support');
    expect($settings->get('notifications.from_email'))->toBe('noreply@example.com');
    expect($settings->get('ui.sidebar_design'))->toBe('design2');

    $this->actingAs($user)
        ->get(route('admin.settings'))
        ->assertOk()
        ->assertSee('H2 Systems')
        ->assertSee('support@example.com')
        ->assertSee('https://zoom.us/j/updated-demo');
});

it('requires company name when saving settings', function () {
    $user = superAdminUser();

    $this->actingAs($user)
        ->from(route('admin.settings'))
        ->put(route('admin.settings.update'), [
            'site_company_name' => '',
            'sidebar_design' => 'design1',
        ])
        ->assertRedirect(route('admin.settings'))
        ->assertSessionHasErrors('site_company_name');
});
