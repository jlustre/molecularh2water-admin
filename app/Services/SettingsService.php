<?php

namespace App\Services;

use App\Models\Setting;
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

    private function cacheKey(string $key): string
    {
        return 'setting.'.md5($key);
    }
}
