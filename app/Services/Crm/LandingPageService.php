<?php

namespace App\Services\Crm;

use App\Models\Crm\Funnel;
use App\Models\Crm\LandingPage;
use App\Models\Crm\LeadCaptureForm;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LandingPageService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): LandingPage
    {
        $slug = $this->uniqueSlug(Arr::get($data, 'slug') ?: Arr::get($data, 'title'));

        $page = LandingPage::query()->create([
            'funnel_id' => Arr::get($data, 'funnel_id'),
            'title' => trim((string) Arr::get($data, 'title')),
            'slug' => $slug,
            'headline' => Arr::get($data, 'headline'),
            'subheadline' => Arr::get($data, 'subheadline'),
            'hero_media' => Arr::get($data, 'hero_media'),
            'cta_label' => Arr::get($data, 'cta_label'),
            'cta_url' => Arr::get($data, 'cta_url'),
            'thank_you_headline' => Arr::get($data, 'thank_you_headline'),
            'thank_you_body' => Arr::get($data, 'thank_you_body'),
            'tracking_source' => Arr::get($data, 'tracking_source'),
            'is_published' => (bool) Arr::get($data, 'is_published', false),
        ]);

        $this->syncForm($page, $data);

        return $page->fresh(['form', 'funnel']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(LandingPage $page, array $data): LandingPage
    {
        $page->update([
            'funnel_id' => Arr::get($data, 'funnel_id', $page->funnel_id),
            'title' => trim((string) Arr::get($data, 'title', $page->title)),
            'slug' => $this->uniqueSlug(
                Arr::get($data, 'slug', $page->slug),
                $page->id,
            ),
            'headline' => Arr::get($data, 'headline', $page->headline),
            'subheadline' => Arr::get($data, 'subheadline', $page->subheadline),
            'hero_media' => Arr::get($data, 'hero_media', $page->hero_media),
            'cta_label' => Arr::get($data, 'cta_label', $page->cta_label),
            'cta_url' => Arr::get($data, 'cta_url', $page->cta_url),
            'thank_you_headline' => Arr::get($data, 'thank_you_headline', $page->thank_you_headline),
            'thank_you_body' => Arr::get($data, 'thank_you_body', $page->thank_you_body),
            'tracking_source' => Arr::get($data, 'tracking_source', $page->tracking_source),
            'is_published' => (bool) Arr::get($data, 'is_published', $page->is_published),
        ]);

        $this->syncForm($page, $data);

        return $page->fresh(['form', 'funnel']);
    }

    public function delete(LandingPage $page): void
    {
        if ($page->conversion_count > 0) {
            throw ValidationException::withMessages([
                'page' => 'Landing pages with conversions cannot be deleted. Unpublish instead.',
            ]);
        }

        $page->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncForm(LandingPage $page, array $data): void
    {
        $fields = Arr::get($data, 'form_fields', config('crm.landing_pages.default_form_fields', []));
        $settings = array_merge(
            config('crm.landing_pages.default_form_settings', []),
            Arr::get($data, 'form_settings', []),
        );

        LeadCaptureForm::query()->updateOrCreate(
            ['landing_page_id' => $page->id],
            [
                'fields' => $fields,
                'settings' => $settings,
            ],
        );
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'landing-page';
        $candidate = $base;
        $counter = 2;

        while (LandingPage::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }
}
