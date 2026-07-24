<?php

namespace App\Support\Navigation;

use App\Enums\WebsiteFormSubmissionStatus;
use App\Enums\WebsiteFormType;
use App\Models\BlogPost;
use App\Models\Crm\Lead;
use App\Models\Faq;
use App\Models\User;
use App\Models\WebsiteFormSubmission;
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
            'content' => 'Content',
            'crm_people' => 'CRM · People',
            'crm_pipeline' => 'CRM · Pipeline',
            'crm_schedule' => 'CRM · Schedule',
            'crm_insights' => 'CRM · Insights',
            'crm_setup' => 'CRM · Setup',
            'engagement' => 'Website Inboxes',
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
        $blogCount = $user->hasPermission('blog.manage') && Schema::hasTable('blog_posts')
            ? BlogPost::query()->where('status', 'published')->count()
            : null;
        $formBadges = $user->hasPermission('website-forms.view')
            ? self::websiteFormNewBadges()
            : [];

        $definitions = [
            [
                'key' => 'admin-dashboard',
                'label' => 'Admin Dashboard',
                'route' => 'admin.dashboard',
                'permission' => null,
                'requires_admin' => true,
                'section' => 'overview',
            ],
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
                'route' => 'admin.blog.index',
                'route_pattern' => 'admin.blog.*',
                'permission' => 'blog.manage',
                'requires_admin' => true,
                'section' => 'content',
                'badge' => $blogCount ?: null,
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

            // CRM · People
            [
                'key' => 'crm-leads',
                'label' => 'Leads',
                'route' => $crmPrefix.'leads.index',
                'route_pattern' => $crmPrefix.'leads.*',
                'permission' => 'leads.view',
                'section' => 'crm_people',
                'badge' => $leadCount,
                'badge_tone' => 'live',
            ],
            [
                'key' => 'crm-prospects',
                'label' => 'Prospects',
                'route' => $crmPrefix.'prospects.index',
                'route_pattern' => $crmPrefix.'prospects.*',
                'permission' => 'prospects.view',
                'section' => 'crm_people',
            ],
            [
                'key' => 'crm-customers',
                'label' => 'Customers',
                'route' => $crmPrefix.'customers.index',
                'route_pattern' => $crmPrefix.'customers.*',
                'permission' => 'clients.view',
                'section' => 'crm_people',
            ],
            [
                'key' => 'crm-recruits',
                'label' => 'Recruits',
                'route' => $crmPrefix.'recruits.index',
                'route_pattern' => $crmPrefix.'recruits.*',
                'permission' => 'recruits.view',
                'section' => 'crm_people',
            ],

            // CRM · Pipeline
            [
                'key' => 'crm-pipeline',
                'label' => 'Funnel Board',
                'route' => $crmPrefix.'pipeline.index',
                'permission' => 'pipeline.view',
                'section' => 'crm_pipeline',
            ],
            [
                'key' => 'crm-funnels',
                'label' => 'Funnel Builder',
                'route' => $crmPrefix.'funnels.index',
                'permission' => 'funnel.manage',
                'section' => 'crm_pipeline',
            ],
            [
                'key' => 'crm-activities',
                'label' => 'Activities',
                'route' => $crmPrefix.'activities.index',
                'permission' => 'activities.view',
                'section' => 'crm_pipeline',
            ],
            [
                'key' => 'crm-sales',
                'label' => 'Consultant Sales',
                'route' => $crmPrefix.'sales.index',
                'permission' => 'sales.view',
                'section' => 'crm_pipeline',
            ],
            [
                'key' => 'crm-products',
                'label' => 'Products & Gifts',
                'route' => $crmPrefix.'products.index',
                'permission' => 'products.view',
                'section' => 'crm_pipeline',
            ],
            [
                'key' => 'crm-inventory',
                'label' => 'Inventory',
                'route' => $crmPrefix.'inventory.index',
                'permission' => 'products.view',
                'section' => 'crm_pipeline',
            ],

            // CRM · Schedule
            [
                'key' => 'crm-calendar',
                'label' => 'Team Calendar',
                'route' => $crmPrefix.'calendar.index',
                'permission' => 'calendar.view',
                'section' => 'crm_schedule',
            ],
            [
                'key' => 'crm-appointments',
                'label' => 'Booked Appointments',
                'route' => $crmPrefix.'appointments.index',
                'permission' => 'appointments.view',
                'section' => 'crm_schedule',
            ],
            [
                'key' => 'crm-tasks',
                'label' => 'Tasks',
                'route' => $crmPrefix.'tasks.index',
                'permission' => 'tasks.view',
                'section' => 'crm_schedule',
            ],

            // CRM · Insights
            [
                'key' => 'crm-dashboard',
                'label' => 'Executive Dashboard',
                'route' => $crmPrefix.'dashboard.index',
                'permission' => 'crm.dashboard.view',
                'section' => 'crm_insights',
            ],
            [
                'key' => 'crm-reports',
                'label' => 'Reports',
                'route' => $crmPrefix.'reports.index',
                'permission' => 'reports.view',
                'section' => 'crm_insights',
            ],

            // CRM · Setup
            [
                'key' => 'crm-landing-pages',
                'label' => 'Landing Pages',
                'route' => $crmPrefix.'landing-pages.index',
                'permission' => 'landing-pages.view',
                'section' => 'crm_setup',
            ],
            [
                'key' => 'crm-settings',
                'label' => 'CRM Settings',
                'route' => $crmPrefix.'settings.index',
                'permission' => 'crm.settings.manage',
                'section' => 'crm_setup',
            ],

            // Website inboxes
            [
                'key' => 'contact-messages',
                'label' => 'Contact Us',
                'route' => 'admin.website-forms.index',
                'route_params' => ['formType' => 'contact-us'],
                'permission' => 'website-forms.view',
                'requires_admin' => true,
                'section' => 'engagement',
                'badge' => $formBadges[WebsiteFormType::ContactUs->value] ?? null,
                'badge_tone' => 'warn',
                'active_when' => fn () => request()->routeIs('admin.website-forms.*')
                    && request()->route('formType') === 'contact-us',
            ],
            [
                'key' => 'water-awareness-shows',
                'label' => 'Water Awareness Shows',
                'route' => 'admin.website-forms.index',
                'route_params' => ['formType' => 'water-awareness-shows'],
                'permission' => 'website-forms.view',
                'requires_admin' => true,
                'section' => 'engagement',
                'badge' => $formBadges[WebsiteFormType::WaterAwarenessShow->value] ?? null,
                'badge_tone' => 'warn',
                'active_when' => fn () => request()->routeIs('admin.website-forms.*')
                    && request()->route('formType') === 'water-awareness-shows',
            ],
            [
                'key' => 'hydration-specialist-zooms',
                'label' => 'Hydration Specialist Zooms',
                'route' => 'admin.website-forms.index',
                'route_params' => ['formType' => 'hydration-specialist-zooms'],
                'permission' => 'website-forms.view',
                'requires_admin' => true,
                'section' => 'engagement',
                'badge' => $formBadges[WebsiteFormType::HydrationSpecialistZoom->value] ?? null,
                'badge_tone' => 'warn',
                'active_when' => fn () => request()->routeIs('admin.website-forms.*')
                    && request()->route('formType') === 'hydration-specialist-zooms',
            ],
            [
                'key' => 'wellness-advocate-zooms',
                'label' => 'Wellness Advocate Zooms',
                'route' => 'admin.website-forms.index',
                'route_params' => ['formType' => 'wellness-advocate-zooms'],
                'permission' => 'website-forms.view',
                'requires_admin' => true,
                'section' => 'engagement',
                'badge' => $formBadges[WebsiteFormType::WellnessAdvocateZoom->value] ?? null,
                'badge_tone' => 'warn',
                'active_when' => fn () => request()->routeIs('admin.website-forms.*')
                    && request()->route('formType') === 'wellness-advocate-zooms',
            ],
            [
                'key' => 'warranty',
                'label' => 'Warranty Registrations',
                'route' => 'admin.warranty-registrations.index',
                'route_pattern' => 'admin.warranty-registrations.*',
                'permission' => 'warranty.view',
                'requires_admin' => true,
                'section' => 'engagement',
            ],
            [
                'key' => 'installation-questionnaires',
                'label' => 'Installation Questionnaires',
                'route' => 'admin.installation-questionnaires.index',
                'route_pattern' => 'admin.installation-questionnaires.*',
                'permission' => 'installation-questionnaires.view',
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
                'permission' => 'roles.view',
                'requires_admin' => true,
                'section' => 'system',
            ],
            [
                'key' => 'email-mappings',
                'label' => 'Email Mappings',
                'route' => 'admin.email-mappings.index',
                'route_pattern' => 'admin.email-mappings.*',
                'permission' => 'email-mappings.view',
                'requires_admin' => true,
                'section' => 'system',
            ],
            [
                'key' => 'website-content',
                'label' => 'Website Content',
                'route' => 'admin.website-content.edit',
                'route_pattern' => 'admin.website-content.*',
                'permission' => 'settings.manage',
                'requires_admin' => true,
                'section' => 'system',
            ],
            [
                'key' => 'settings',
                'label' => 'Settings',
                'route' => 'admin.settings',
                'route_pattern' => 'admin.settings*',
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
        ];

        return collect($definitions)
            ->filter(fn (array $link) => self::userCanSee($user, $link))
            ->map(function (array $link) {
                $route = $link['route'] ?? null;
                $routeParams = $link['route_params'] ?? [];
                $href = $route ? route($route, $routeParams) : ($link['href'] ?? '#');
                $pattern = $link['route_pattern'] ?? $route;
                $active = isset($link['active_when']) && is_callable($link['active_when'])
                    ? (bool) ($link['active_when'])()
                    : ($pattern ? request()->routeIs($pattern) : false);

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

    /**
     * @return array<string, int>
     */
    protected static function websiteFormNewBadges(): array
    {
        if (! Schema::hasTable('website_form_submissions')) {
            return [];
        }

        return WebsiteFormSubmission::query()
            ->where('status', WebsiteFormSubmissionStatus::New)
            ->selectRaw('form_type, count(*) as aggregate')
            ->groupBy('form_type')
            ->pluck('aggregate', 'form_type')
            ->map(fn ($count) => (int) $count)
            ->filter(fn (int $count) => $count > 0)
            ->all();
    }
}
