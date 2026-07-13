<?php

namespace App\Support\Navigation;

use App\Models\Crm\Lead;
use App\Models\Faq;
use App\Models\User;
use App\Support\Crm\CrmRoutes;
use App\Support\Crm\CrmScope;
use Illuminate\Support\Facades\Schema;

class AppNavigation
{
    /**
     * Section key => label for sidebar groups (empty groups are hidden).
     *
     * @return array<string, string>
     */
    public static function sections(): array
    {
        return [
            'overview' => 'Overview',
            'workspace' => 'Workspace',
            'content' => 'Content Management',
            'crm' => 'CRM & Sales',
            'engagement' => 'Customer Engagement',
            'system' => 'System',
            'account' => 'Account',
        ];
    }

    /**
     * Permission-filtered navigation links for the authenticated shell sidebar.
     *
     * @return list<array{
     *     key: string,
     *     label: string,
     *     href: string,
     *     route: string|null,
     *     route_pattern: string|null,
     *     permission: string|null,
     *     section: string,
     *     active: bool,
     *     badge: string|int|null,
     *     badge_tone: string|null,
     *     wire_navigate: bool
     * }>
     */
    public static function links(?User $user = null): array
    {
        $user ??= auth()->user();

        if (! $user) {
            return [];
        }

        $crmPrefix = CrmRoutes::prefixForUser($user);
        $leadCount = $user->hasPermission('leads.view') ? self::leadCountBadge() : null;
        $faqCount = $user->hasPermission('faqs.manage') && Schema::hasTable('faqs')
            ? Faq::query()->count()
            : null;

        $definitions = [
            // Overview
            [
                'key' => 'admin-dashboard',
                'label' => 'Admin Dashboard',
                'route' => 'admin.dashboard',
                'permission' => null,
                'requires_admin' => true,
                'section' => 'overview',
                'badge' => 'Live',
                'badge_tone' => 'live',
            ],

            // Workspace
            [
                'key' => 'portal-dashboard',
                'label' => 'Dashboard',
                'route' => 'dashboard',
                'permission' => 'portal.dashboard.view',
                'section' => 'workspace',
            ],
            [
                'key' => 'profile',
                'label' => 'My Profile',
                'route' => 'profile',
                'permission' => null,
                'section' => 'workspace',
            ],
            [
                'key' => 'resources',
                'label' => 'Resources',
                'route' => 'resources',
                'permission' => 'portal.dashboard.view',
                'section' => 'workspace',
            ],

            // Content (admin)
            [
                'key' => 'faqs',
                'label' => 'FAQs',
                'route' => 'admin.faqs.index',
                'route_pattern' => 'admin.faqs.*',
                'permission' => 'faqs.manage',
                'requires_admin' => true,
                'section' => 'content',
                'badge' => $faqCount,
            ],
            [
                'key' => 'blog',
                'label' => 'Blog / Education',
                'route' => 'admin.blog',
                'permission' => 'blog.manage',
                'requires_admin' => true,
                'section' => 'content',
                'badge' => '3 New',
                'badge_tone' => 'live',
            ],
            [
                'key' => 'media',
                'label' => 'Media Library',
                'route' => 'admin.media.index',
                'route_pattern' => 'admin.media.*',
                'permission' => 'media.view',
                'requires_admin' => true,
                'section' => 'content',
            ],

            // CRM (context-aware admin/portal prefix)
            [
                'key' => 'crm-leads',
                'label' => 'Leads',
                'route' => $crmPrefix.'leads.index',
                'route_pattern' => $crmPrefix.'leads.*',
                'permission' => 'leads.view',
                'section' => 'crm',
                'badge' => $leadCount,
                'badge_tone' => 'live',
            ],
            [
                'key' => 'crm-prospects',
                'label' => 'Prospects',
                'route' => $crmPrefix.'prospects.index',
                'route_pattern' => $crmPrefix.'prospects.*',
                'permission' => 'prospects.view',
                'section' => 'crm',
            ],
            [
                'key' => 'crm-customers',
                'label' => 'Customers',
                'route' => $crmPrefix.'customers.index',
                'route_pattern' => $crmPrefix.'customers.*',
                'permission' => 'clients.view',
                'section' => 'crm',
            ],
            [
                'key' => 'crm-recruits',
                'label' => 'Recruits',
                'route' => $crmPrefix.'recruits.index',
                'route_pattern' => $crmPrefix.'recruits.*',
                'permission' => 'recruits.view',
                'section' => 'crm',
            ],
            [
                'key' => 'crm-pipeline',
                'label' => 'Funnel Board',
                'route' => $crmPrefix.'pipeline.index',
                'permission' => 'pipeline.view',
                'section' => 'crm',
            ],
            [
                'key' => 'crm-funnels',
                'label' => 'Funnel Builder',
                'route' => $crmPrefix.'funnels.index',
                'permission' => 'funnel.manage',
                'section' => 'crm',
            ],
            [
                'key' => 'crm-activities',
                'label' => 'Activities',
                'route' => $crmPrefix.'activities.index',
                'permission' => 'activities.view',
                'section' => 'crm',
            ],
            [
                'key' => 'crm-sales',
                'label' => 'Sales',
                'route' => $crmPrefix.'sales.index',
                'permission' => 'sales.view',
                'section' => 'crm',
            ],
            [
                'key' => 'crm-tasks',
                'label' => 'Tasks',
                'route' => $crmPrefix.'tasks.index',
                'permission' => 'tasks.view',
                'section' => 'crm',
            ],
            [
                'key' => 'crm-calendar',
                'label' => 'Calendar',
                'route' => $crmPrefix.'calendar.index',
                'permission' => 'calendar.view',
                'section' => 'crm',
            ],
            [
                'key' => 'crm-appointments',
                'label' => 'Appointments',
                'route' => $crmPrefix.'appointments.index',
                'permission' => 'appointments.view',
                'section' => 'crm',
            ],
            [
                'key' => 'crm-landing-pages',
                'label' => 'Landing Pages',
                'route' => $crmPrefix.'landing-pages.index',
                'permission' => 'landing-pages.view',
                'section' => 'crm',
            ],
            [
                'key' => 'crm-dashboard',
                'label' => 'Executive Dashboard',
                'route' => $crmPrefix.'dashboard.index',
                'permission' => 'crm.dashboard.view',
                'section' => 'crm',
            ],
            [
                'key' => 'crm-reports',
                'label' => 'Reports',
                'route' => $crmPrefix.'reports.index',
                'permission' => 'reports.view',
                'section' => 'crm',
            ],
            [
                'key' => 'crm-settings',
                'label' => 'CRM Settings',
                'route' => $crmPrefix.'settings.index',
                'permission' => 'crm.settings.manage',
                'section' => 'crm',
            ],

            // Customer engagement (admin legacy entry points)
            [
                'key' => 'engagement-leads',
                'label' => 'Leads',
                'route' => 'admin.leads',
                'permission' => 'leads.view',
                'requires_admin' => true,
                'section' => 'engagement',
            ],
            [
                'key' => 'contact-messages',
                'label' => 'Contact Messages',
                'route' => 'admin.contact-messages',
                'permission' => 'messages.manage',
                'requires_admin' => true,
                'section' => 'engagement',
                'badge' => '7',
                'badge_tone' => 'warn',
            ],
            [
                'key' => 'engagement-appointments',
                'label' => 'Appointments',
                'route' => 'admin.appointments',
                'permission' => 'appointments.view',
                'requires_admin' => true,
                'section' => 'engagement',
            ],
            [
                'key' => 'warranty',
                'label' => 'Warranty Registrations',
                'route' => 'admin.warranty-registrations.index',
                'route_pattern' => 'admin.warranty-registrations.*',
                'permission' => null,
                'requires_admin' => true,
                'section' => 'engagement',
            ],

            // System
            [
                'key' => 'users',
                'label' => 'Users',
                'route' => 'admin.users.index',
                'route_pattern' => 'admin.users.*',
                'permission' => 'users.view',
                'requires_admin' => true,
                'section' => 'system',
            ],
            [
                'key' => 'roles',
                'label' => 'Roles & Permissions',
                'route' => 'admin.roles.index',
                'route_pattern' => 'admin.roles.*',
                'permission' => null,
                'requires_admin' => true,
                'section' => 'system',
            ],
            [
                'key' => 'settings',
                'label' => 'Settings',
                'route' => 'admin.settings',
                'permission' => 'settings.manage',
                'requires_admin' => true,
                'section' => 'system',
            ],

            // Account
            [
                'key' => 'invites',
                'label' => 'Member Invites',
                'route' => 'portal.invites',
                'permission' => 'invites.manage',
                'section' => 'account',
            ],
            [
                'key' => 'team',
                'label' => 'My Team',
                'route' => 'portal.team',
                'permission' => 'sponsors.view-tree',
                'section' => 'account',
            ],
            [
                'key' => 'admin-portal',
                'label' => 'Admin Portal',
                'route' => 'admin.dashboard',
                'permission' => null,
                'requires_admin' => true,
                'section' => 'account',
            ],
        ];

        return collect($definitions)
            ->filter(fn (array $link) => self::userCanSee($user, $link))
            ->map(function (array $link) {
                $route = $link['route'] ?? null;
                $href = $route ? route($route) : ($link['href'] ?? '#');
                $pattern = $link['route_pattern'] ?? $route;
                $active = $pattern ? request()->routeIs($pattern) : false;

                return [
                    'key' => $link['key'],
                    'label' => $link['label'],
                    'href' => $href,
                    'route' => $route,
                    'route_pattern' => $pattern,
                    'permission' => $link['permission'] ?? null,
                    'section' => $link['section'],
                    'active' => $active,
                    'badge' => $link['badge'] ?? null,
                    'badge_tone' => $link['badge_tone'] ?? null,
                    'wire_navigate' => $route !== null && ! str_starts_with($route, 'admin.'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function activeSections(?User $user = null): array
    {
        return collect(self::links($user))
            ->filter(fn (array $link) => $link['active'])
            ->pluck('section')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Home URL for the brand logo.
     */
    public static function homeUrl(?User $user = null): string
    {
        $user ??= auth()->user();

        if (request()->routeIs('admin.*') && $user?->canAccessAdmin()) {
            return route('admin.dashboard');
        }

        if ($user?->canAccessPortal()) {
            return route('dashboard');
        }

        if ($user?->canAccessAdmin()) {
            return route('admin.dashboard');
        }

        return route('dashboard');
    }

    /**
     * @param  array{permission?: string|null, requires_admin?: bool}  $link
     */
    protected static function userCanSee(User $user, array $link): bool
    {
        if (! empty($link['requires_admin']) && ! $user->canAccessAdmin()) {
            return false;
        }

        if (empty($link['permission'])) {
            return true;
        }

        return $user->hasPermission($link['permission']);
    }

    protected static function leadCountBadge(): ?int
    {
        if (! Schema::hasTable('leads')) {
            return null;
        }

        $count = CrmScope::leads(Lead::query())->lifecycle('lead')->count();

        return $count > 0 ? $count : null;
    }
}
