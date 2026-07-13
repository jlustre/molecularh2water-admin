<?php

namespace App\Support\Crm;

class CrmPermissions
{
    /**
     * All CRM permission keys.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::flat());
    }

    /**
     * Flat permission key => label map.
     *
     * @return array<string, string>
     */
    public static function flat(): array
    {
        $flat = [];

        foreach (self::groups() as $group) {
            foreach ($group['items'] as $key => $label) {
                $flat[$key] = $label;
            }
        }

        return $flat;
    }

    /**
     * Permission groups for role management UI.
     *
     * @return array<string, array{label: string, items: array<string, string>}>
     */
    public static function groups(): array
    {
        return [
            'dashboard' => [
                'label' => 'Dashboard',
                'items' => [
                    'admin.dashboard.view' => 'View admin dashboard',
                    'crm.dashboard.view' => 'View CRM dashboard metrics',
                    'portal.dashboard.view' => 'View associate portal dashboard',
                ],
            ],
            'media' => [
                'label' => 'Media Library',
                'items' => [
                    'media.view' => 'View media',
                    'media.create' => 'Create media',
                    'media.update' => 'Update media',
                    'media.delete' => 'Delete media',
                    'media.export' => 'Update media seeder',
                ],
            ],
            'users' => [
                'label' => 'Users',
                'items' => [
                    'users.view' => 'View users',
                    'users.create' => 'Create users',
                    'users.update' => 'Update users',
                    'users.delete' => 'Delete users',
                    'users.export' => 'Update users seeder',
                ],
            ],
            'content' => [
                'label' => 'Content',
                'items' => [
                    'blog.manage' => 'Manage blog',
                    'faqs.manage' => 'Manage FAQs',
                ],
            ],
            'leads' => [
                'label' => 'Leads',
                'items' => [
                    'leads.view' => 'View leads',
                    'leads.create' => 'Create leads',
                    'leads.update' => 'Update leads',
                    'leads.delete' => 'Delete leads',
                    'leads.import' => 'Import leads',
                    'leads.export' => 'Export leads',
                    'leads.assign' => 'Assign leads to users',
                    'leads.manage' => 'Manage leads (legacy)',
                ],
            ],
            'prospects' => [
                'label' => 'Prospects',
                'items' => [
                    'prospects.view' => 'View prospects',
                    'prospects.manage' => 'Manage prospects',
                ],
            ],
            'clients' => [
                'label' => 'Customers',
                'items' => [
                    'clients.view' => 'View customers',
                    'clients.manage' => 'Manage customers',
                ],
            ],
            'recruits' => [
                'label' => 'Recruits',
                'items' => [
                    'recruits.view' => 'View recruits',
                    'recruits.manage' => 'Manage recruits',
                ],
            ],
            'funnel' => [
                'label' => 'Funnels',
                'items' => [
                    'funnel.view' => 'View funnels',
                    'funnel.manage' => 'Manage funnel stages',
                    'pipeline.view' => 'View pipeline board',
                    'pipeline.manage' => 'Move leads on pipeline',
                ],
            ],
            'activities' => [
                'label' => 'Activities',
                'items' => [
                    'activities.view' => 'View activities',
                    'activities.manage' => 'Log and manage activities',
                ],
            ],
            'sales' => [
                'label' => 'Sales',
                'items' => [
                    'sales.view' => 'View sales overview (orders and quotations)',
                ],
            ],
            'tasks' => [
                'label' => 'Tasks',
                'items' => [
                    'tasks.view' => 'View tasks',
                    'tasks.manage' => 'Manage tasks and follow-ups',
                ],
            ],
            'appointments' => [
                'label' => 'Appointments',
                'items' => [
                    'appointments.view' => 'View appointments',
                    'appointments.manage' => 'Manage appointments',
                    'messages.manage' => 'Manage contact messages (legacy)',
                ],
            ],
            'calendar' => [
                'label' => 'Calendar',
                'items' => [
                    'calendar.view' => 'View CRM calendar',
                    'calendar.manage' => 'Manage calendar events',
                    'calendar.view-team' => 'View team calendar events',
                    'calendar.view-all' => 'View all calendar events',
                ],
            ],
            'landing_pages' => [
                'label' => 'Landing Pages',
                'items' => [
                    'landing-pages.view' => 'View landing pages',
                    'landing-pages.manage' => 'Manage landing pages and forms',
                ],
            ],
            'reports' => [
                'label' => 'Reports',
                'items' => [
                    'reports.view' => 'View CRM reports and analytics',
                ],
            ],
            'crm_admin' => [
                'label' => 'CRM Administration',
                'items' => [
                    'crm.records.view-all' => 'View all users CRM records',
                    'crm.records.view-team' => 'View CRM records for team members',
                    'crm.teams.manage' => 'Manage teams',
                    'crm.settings.manage' => 'Manage CRM settings',
                    'notifications.view' => 'View notifications',
                    'settings.manage' => 'Manage system settings',
                ],
            ],
            'portal' => [
                'label' => 'Member Portal',
                'items' => [
                    'invites.manage' => 'Generate sponsor registration invites',
                    'sponsors.view-tree' => 'View member sponsor hierarchy',
                ],
            ],
        ];
    }

    /**
     * Default permissions by role slug.
     *
     * @return array<string, list<string>>
     */
    public static function defaultsByRole(): array
    {
        $all = self::all();

        $consultant = [
            'portal.dashboard.view',
            'invites.manage',
            'sponsors.view-tree',
            'crm.dashboard.view',
            'leads.view',
            'leads.create',
            'leads.update',
            'prospects.view',
            'clients.view',
            'recruits.view',
            'pipeline.view',
            'pipeline.manage',
            'activities.view',
            'activities.manage',
            'sales.view',
            'tasks.view',
            'tasks.manage',
            'appointments.view',
            'appointments.manage',
            'calendar.view',
            'calendar.manage',
            'notifications.view',
        ];

        return [
            'super-admin' => $all,
            'admin' => array_values(array_diff($all, ['users.delete', 'settings.manage'])),
            'team-admin' => array_values(array_diff($all, [
                'users.delete',
                'settings.manage',
                'crm.records.view-all',
            ])),
            'manager' => [
                'portal.dashboard.view',
                'invites.manage',
                'sponsors.view-tree',
                'crm.dashboard.view',
                'media.view',
                'media.create',
                'media.update',
                'media.export',
                'leads.view',
                'leads.create',
                'leads.update',
                'leads.import',
                'leads.export',
                'prospects.view',
                'prospects.manage',
                'clients.view',
                'clients.manage',
                'recruits.view',
                'recruits.manage',
                'funnel.view',
                'pipeline.view',
                'pipeline.manage',
                'activities.view',
                'activities.manage',
                'sales.view',
                'tasks.view',
                'tasks.manage',
                'appointments.view',
                'appointments.manage',
                'calendar.view',
                'calendar.manage',
                'calendar.view-team',
                'crm.records.view-team',
                'landing-pages.view',
                'reports.view',
                'notifications.view',
            ],
            'consultant' => $consultant,
            'agent' => $consultant,
            'editor' => [
                'admin.dashboard.view',
                'portal.dashboard.view',
                'media.view',
                'media.create',
                'media.update',
                'blog.manage',
                'faqs.manage',
            ],
            'member' => array_values(array_unique(array_merge($consultant, [
                'media.view',
            ]))),
        ];
    }
}
