<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use App\Support\FrontendUrl;
use App\Support\WebsiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebsiteContentController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function edit(): View
    {
        $values = $this->settings->websiteContent();

        return view('admin.website-content.edit', [
            'fields' => WebsiteContent::fields(),
            'frontendUrl' => FrontendUrl::base(),
            'values' => $values,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [];

        foreach (WebsiteContent::fields() as $key => $field) {
            $input = str_replace('.', '_', $key);

            $rules[$input] = match ($field['type']) {
                'email' => ['nullable', 'email', 'max:255'],
                'url' => ['nullable', 'url', 'max:500'],
                default => ['nullable', 'string', 'max:255'],
            };

            if ($key === 'site.company_name') {
                $rules[$input] = ['required', 'string', 'max:255'];
            }
        }

        $validated = $request->validate($rules);
        $payload = [];

        foreach (WebsiteContent::keys() as $key) {
            $input = str_replace('.', '_', $key);
            $value = $validated[$input] ?? null;
            $payload[$key] = filled($value) ? trim((string) $value) : null;
        }

        // Keep required company name filled even if somehow blank after trim.
        $payload['site.company_name'] = $payload['site.company_name']
            ?: WebsiteContent::defaults()['site.company_name'];

        $this->settings->setMany($payload);

        return redirect()
            ->route('admin.website-content.edit')
            ->with('status', 'Website content saved. Public pages will use the updated details.');
    }
}
