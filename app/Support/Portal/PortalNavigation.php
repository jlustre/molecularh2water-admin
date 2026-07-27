<?php

namespace App\Support\Portal;

use App\Models\User;
use App\Support\Navigation\AppNavigation;

class PortalNavigation
{
    /**
     * Portal-oriented subset of the shared app navigation.
     *
     * @return list<array{label: string, route: string|null, href: string|null, permission: string|null, active: bool, section?: string}>
     */
    public static function links(?User $user = null): array
    {
        $portalSections = [
            'workspace',
            'crm_people',
            'crm_pipeline',
            'crm_insights',
            'crm_setup',
        ];

        return collect(AppNavigation::links($user))
            ->filter(fn (array $link) => in_array($link['section'], $portalSections, true))
            ->map(function (array $link) {
                // Preserve prior portal label for the pipeline board.
                if ($link['key'] === 'crm-pipeline') {
                    $link['label'] = 'Pipeline';
                }

                return [
                    'label' => $link['label'],
                    'route' => $link['route'],
                    'href' => $link['href'],
                    'permission' => $link['permission'],
                    'active' => $link['active'],
                    'section' => $link['section'],
                    'route_pattern' => $link['route_pattern'],
                ];
            })
            ->values()
            ->all();
    }
}
