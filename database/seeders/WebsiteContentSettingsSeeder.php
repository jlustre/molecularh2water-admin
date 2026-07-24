<?php

namespace Database\Seeders;

use App\Services\SettingsService;
use App\Support\WebsiteContent;
use Illuminate\Database\Seeder;

class WebsiteContentSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = app(SettingsService::class);

        foreach (WebsiteContent::defaults() as $key => $default) {
            if ($settings->get($key) === null) {
                $settings->set($key, $default);
            }
        }
    }
}
