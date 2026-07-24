<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    private const SIDEBAR_DESIGNS = [
        'design1' => 'Design 1',
        'design2' => 'Design 2',
        'design3' => 'Design 3',
    ];

    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function edit(): View
    {
        $sidebarDesign = $this->settings->get('ui.sidebar_design')
            ?? session('sidebar_design', 'design1');

        return view('admin.settings.edit', [
            'settings' => [
                'site.company_name' => $this->settings->get('site.company_name', 'Molecular H2 Water'),
                'site.support_email' => $this->settings->get('site.support_email'),
                'site.support_phone' => $this->settings->get('site.support_phone'),
                'portal.online_demo_link' => $this->settings->get('portal.online_demo_link'),
                'notifications.from_name' => $this->settings->get('notifications.from_name'),
                'notifications.from_email' => $this->settings->get('notifications.from_email'),
                'ui.sidebar_design' => $sidebarDesign,
            ],
            'sidebarDesigns' => self::SIDEBAR_DESIGNS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_company_name' => ['required', 'string', 'max:255'],
            'site_support_email' => ['nullable', 'email', 'max:255'],
            'site_support_phone' => ['nullable', 'string', 'max:50'],
            'portal_online_demo_link' => ['nullable', 'url', 'max:500'],
            'notifications_from_name' => ['nullable', 'string', 'max:255'],
            'notifications_from_email' => ['nullable', 'email', 'max:255'],
            'sidebar_design' => ['required', Rule::in(array_keys(self::SIDEBAR_DESIGNS))],
        ]);

        $this->settings->set('site.company_name', $validated['site_company_name']);
        $this->settings->set('site.support_email', $validated['site_support_email'] ?? null);
        $this->settings->set('site.support_phone', $validated['site_support_phone'] ?? null);
        $this->settings->set('portal.online_demo_link', $validated['portal_online_demo_link'] ?? null);
        $this->settings->set('notifications.from_name', $validated['notifications_from_name'] ?? null);
        $this->settings->set('notifications.from_email', $validated['notifications_from_email'] ?? null);
        $this->settings->set('ui.sidebar_design', $validated['sidebar_design']);

        session(['sidebar_design' => $validated['sidebar_design']]);

        return redirect()
            ->route('admin.settings')
            ->with('status', 'Settings saved.');
    }
}
