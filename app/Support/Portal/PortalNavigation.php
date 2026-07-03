<?php

namespace App\Support\Portal;

use App\Models\User;

class PortalNavigation
{
    /**
     * @return list<array{label: string, route: string|null, href: string|null, permission: string|null, active: bool, section?: string}>
     */
    public static function links(?User $user = null): array
    {
        $user ??= auth()->user();

        if (! $user) {
            return [];
        }

        $links = [
            [
                'label' => 'Dashboard',
                'route' => 'dashboard',
                'href' => null,
                'permission' => 'portal.dashboard.view',
                'section' => 'workspace',
            ],
            [
                'label' => 'My Profile',
                'route' => 'profile',
                'href' => null,
                'permission' => null,
                'section' => 'workspace',
            ],
            [
                'label' => 'Resources',
                'route' => 'resources',
                'href' => null,
                'permission' => 'portal.dashboard.view',
                'section' => 'workspace',
            ],
        ];

        if ($user->hasPermission('crm.dashboard.view') || $user->hasPermission('leads.view')) {
            $crm = [
                ['label' => 'Leads', 'route' => 'portal.crm.leads.index', 'route_pattern' => 'portal.crm.leads.*', 'permission' => 'leads.view'],
                ['label' => 'Prospects', 'route' => 'portal.crm.prospects.index', 'route_pattern' => 'portal.crm.prospects.*', 'permission' => 'prospects.view'],
                ['label' => 'Customers', 'route' => 'portal.crm.customers.index', 'route_pattern' => 'portal.crm.customers.*', 'permission' => 'clients.view'],
                ['label' => 'Recruits', 'route' => 'portal.crm.recruits.index', 'route_pattern' => 'portal.crm.recruits.*', 'permission' => 'recruits.view'],
                ['label' => 'Pipeline', 'route' => 'portal.crm.pipeline.index', 'permission' => 'pipeline.view'],
                ['label' => 'Calendar', 'route' => 'portal.crm.calendar.index', 'permission' => 'calendar.view'],
                ['label' => 'Tasks', 'route' => 'portal.crm.tasks.index', 'permission' => 'tasks.view'],
                ['label' => 'Activities', 'route' => 'portal.crm.activities.index', 'permission' => 'activities.view'],
                ['label' => 'Sales', 'route' => 'portal.crm.sales.index', 'permission' => 'sales.view'],
            ];

            foreach ($crm as $item) {
                if ($user->hasPermission($item['permission'])) {
                    $links[] = [
                        'label' => $item['label'],
                        'route' => $item['route'],
                        'route_pattern' => $item['route_pattern'] ?? null,
                        'href' => null,
                        'permission' => $item['permission'],
                        'section' => 'crm',
                    ];
                }
            }

            if ($user->hasPermission('crm.dashboard.view')) {
                $links[] = [
                    'label' => 'Executive Dashboard',
                    'route' => 'portal.crm.dashboard.index',
                    'href' => null,
                    'permission' => 'crm.dashboard.view',
                    'section' => 'crm',
                ];
            }

            if ($user->hasPermission('reports.view')) {
                $links[] = [
                    'label' => 'Reports',
                    'route' => 'portal.crm.reports.index',
                    'href' => null,
                    'permission' => 'reports.view',
                    'section' => 'crm',
                ];
            }
        }

        if ($user->hasPermission('invites.manage')) {
            $links[] = [
                'label' => 'Member Invites',
                'route' => 'portal.invites',
                'href' => null,
                'permission' => 'invites.manage',
                'section' => 'account',
            ];
        }

        if ($user->hasPermission('sponsors.view-tree')) {
            $links[] = [
                'label' => 'My Team',
                'route' => 'portal.team',
                'href' => null,
                'permission' => 'sponsors.view-tree',
                'section' => 'account',
            ];
        }

        if ($user->canAccessAdmin()) {
            $links[] = [
                'label' => 'Admin Portal',
                'route' => 'admin.dashboard',
                'href' => null,
                'permission' => null,
                'requires_admin' => true,
                'section' => 'account',
            ];
        }

        return collect($links)
            ->filter(function (array $link) use ($user) {
                if (! empty($link['requires_admin']) && ! $user->canAccessAdmin()) {
                    return false;
                }

                return ! $link['permission'] || $user->hasPermission($link['permission']);
            })
            ->map(function (array $link) {
                $link['href'] = $link['route'] ? route($link['route']) : $link['href'];
                $link['active'] = ! empty($link['route_pattern'])
                    ? request()->routeIs($link['route_pattern'])
                    : ($link['route'] ? request()->routeIs($link['route']) : false);

                return $link;
            })
            ->values()
            ->all();
    }
}
