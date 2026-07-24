<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\WebsiteContent;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    public function get(string $key, ?string $default = null): ?string
    {
        return Cache::rememberForever($this->cacheKey($key), function () use ($key, $default) {
            $value = Setting::query()->where('key', $key)->value('value');

            return filled($value) ? (string) $value : $default;
        });
    }

    public function set(string $key, ?string $value): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );

        Cache::forget($this->cacheKey($key));
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * @return array<string, string>
     */
    public function websiteContent(): array
    {
        $content = [];

        foreach (WebsiteContent::defaults() as $key => $default) {
            $content[$key] = (string) ($this->get($key, $default) ?? $default);
        }

        return $content;
    }

    /**
     * Public payload for the marketing website.
     *
     * @return array{
     *     company_name: string,
     *     email: string,
     *     phone: string,
     *     phone_tel: string,
     *     location: string,
     *     facebook_url: string,
     *     youtube_url: string,
     *     consumers_guide_url: string
     * }
     */
    public function publicWebsiteContent(): array
    {
        $content = $this->websiteContent();
        $phone = $content['site.support_phone'];

        return [
            'company_name' => $content['site.company_name'],
            'email' => $content['site.support_email'],
            'phone' => $phone,
            'phone_tel' => preg_replace('/\D+/', '', $phone) ?: '',
            'location' => $content['site.location'],
            'facebook_url' => $content['site.facebook_url'],
            'youtube_url' => $content['site.youtube_url'],
            'consumers_guide_url' => $content['site.consumers_guide_url'],
        ];
    }

    private function cacheKey(string $key): string
    {
        return 'setting.'.md5($key);
    }
}

