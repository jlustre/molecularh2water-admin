<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crm\LandingPage;
use Illuminate\Http\JsonResponse;

class LandingPageController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $page = LandingPage::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->with('form')
            ->firstOrFail();

        return response()->json([
            'data' => [
                'id' => $page->id,
                'slug' => $page->slug,
                'title' => $page->title,
                'headline' => $page->headline,
                'subheadline' => $page->subheadline,
                'hero_media' => $page->hero_media,
                'cta_label' => $page->cta_label,
                'cta_url' => $page->cta_url,
                'thank_you_headline' => $page->thank_you_headline,
                'thank_you_body' => $page->thank_you_body,
                'tracking_source' => $page->tracking_source,
                'form' => [
                    'fields' => $page->form?->fields ?? config('crm.landing_pages.default_form_fields', []),
                    'settings' => $page->form?->settings ?? config('crm.landing_pages.default_form_settings', []),
                ],
            ],
        ]);
    }
}
