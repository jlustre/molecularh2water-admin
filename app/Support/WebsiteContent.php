<?php

namespace App\Support;

class WebsiteContent
{
    /**
     * Public website fields managed in admin and exposed via API.
     *
     * @return array<string, array{label: string, type: string, default: string, help: string}>
     */
    public static function fields(): array
    {
        return [
            'site.company_name' => [
                'label' => 'Company name',
                'type' => 'text',
                'default' => 'Molecular H2 Water',
                'help' => 'Brand name shown across the public website.',
            ],
            'site.support_email' => [
                'label' => 'Public email',
                'type' => 'email',
                'default' => 'info@molecularh2water.com',
                'help' => 'Primary contact email used in footer and contact sections.',
            ],
            'site.support_phone' => [
                'label' => 'Public phone',
                'type' => 'text',
                'default' => '(000) 000-0000',
                'help' => 'Display phone number. Digits are used automatically for click-to-call links.',
            ],
            'site.location' => [
                'label' => 'Location',
                'type' => 'text',
                'default' => 'Your City, State',
                'help' => 'City/region shown on the About contact card.',
            ],
            'site.facebook_url' => [
                'label' => 'Facebook URL',
                'type' => 'url',
                'default' => 'https://www.facebook.com/groups/1596145219185739/permalink/1596169665849961/?',
                'help' => 'Facebook link shown in the website footer.',
            ],
            'site.youtube_url' => [
                'label' => 'YouTube URL',
                'type' => 'url',
                'default' => 'https://www.youtube.com/@HydrogenHeals',
                'help' => 'YouTube channel link shown in the website footer.',
            ],
            'site.consumers_guide_url' => [
                'label' => 'Consumers Guide URL',
                'type' => 'url',
                'default' => 'https://heyzine.com/flip-book/c249c5d00b.html',
                'help' => 'Consumers Guide booklet link shown in the website footer.',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        return collect(self::fields())
            ->mapWithKeys(fn (array $field, string $key) => [$key => $field['default']])
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::fields());
    }
}
