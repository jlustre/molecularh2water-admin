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
                ],
            ],
            'media' => [
                'label' => 'Media Library (Content)',
                'items' => [
                    'media.view' => 'View media library',
                    'media.create' => 'Create media',
                    'media.update' => 'Update media',
                    'media.delete' => 'Delete media',
                    'media.export' => 'Update media seeder',
                ],
            ],
            'users' => [
                'label' => 'Users (System)',
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
                    'blog.manage' => 'Manage Blog / Education',
                    'faqs.manage' => 'Manage FAQs',
                ],
            ],
            'warranty' => [
                'label' => 'Warranty Registrations',
                'items' => [
                    'warranty.view' => 'View warranty registrations',
                    'warranty.manage' => 'Manage warranty registrations',
                    'warranty.export' => 'Update warranty seeder',
                ],
            ],
            'installation_questionnaires' => [
                'label' => 'Installation Questionnaires',
                'items' => [
                    'installation-questionnaires.view' => 'View installation questionnaires',
                    'installation-questionnaires.manage' => 'Manage installation questionnaires',
                ],
            ],
            'issue_reports' => [
                'label' => 'Issue Reports',
                'items' => [
                    'issue-reports.view' => 'View issue reports',
                    'issue-reports.manage' => 'Create, update, and delete issue reports',
                ],
            ],
            'installers' => [
                'label' => 'Installer Management',
                'items' => [
                    'installers.view' => 'View installers and installation history',
                    'installers.manage' => 'Create, update, archive, and delete installers',
                ],
            ],
            'customer_directory' => [
                'label' => 'Customers Management',
                'items' => [
                    'customer-directory.view' => 'View customer directory',
                    'customer-directory.manage' => 'Create, update, and delete directory customers',
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
                'label' => 'Sales & Catalog (System)',
                'items' => [
                    'sales.view' => 'View Consultant Sales (System)',
                    'sales.manage' => 'Manage Consultant Sales (System)',
                    'products.view' => 'View Products, Gifts & Inventory (System)',
                    'products.manage' => 'Manage Products, Gifts & Inventory (System)',
                ],
            ],
            'fulfillment' => [
                'label' => 'Orders & Fulfillment',
                'items' => [
                    'fulfillment.view' => 'View deliveries, installations, and ready-to-ship queues',
                    'fulfillment.manage' => 'Update fulfillment status, photos, and installation records',
                ],
            ],
            'tasks' => [
                'label' => 'Tasks',
                'items' => [
                    'tasks.view' => 'View My Tasks (Workspace)',
                    'tasks.manage' => 'Manage My Tasks and follow-ups (Workspace)',
                    'tasks.assign' => 'Tasks Management: assign to any portal member (System)',
                ],
            ],
            'appointments' => [
                'label' => 'Appointments',
                'items' => [
                    'appointments.view' => 'View appointments (Workspace)',
                    'appointments.manage' => 'Manage appointments (Workspace)',
                ],
            ],
            'website_forms' => [
                'label' => 'Website Forms',
                'items' => [
                    'website-forms.view' => 'View website form submissions',
                    'website-forms.manage' => 'Create and update website form submissions',
                    'messages.manage' => 'Manage contact messages (legacy)',
                ],
            ],
            'email_mappings' => [
                'label' => 'Email Mappings',
                'items' => [
                    'email-mappings.view' => 'View form notification email mappings',
                    'email-mappings.manage' => 'Create and update form notification email mappings',
                ],
            ],
            'calendar' => [
                'label' => 'Calendar',
                'items' => [
                    'calendar.view' => 'View My Calendar (Workspace)',
                    'calendar.manage' => 'Manage My Calendar events (Workspace)',
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
                'label' => 'CRM Insights',
                'items' => [
                    'reports.view' => 'View CRM reports and analytics (Insights)',
                    'crm.dashboard.view' => 'View Executive Dashboard (Insights)',
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
                    'settings.manage' => 'Manage system settings and website content',
                ],
            ],
            'roles' => [
                'label' => 'Roles',
                'items' => [
                    'roles.view' => 'View roles',
                    'roles.manage' => 'Create and update roles',
                    'roles.export' => 'Update roles seeder',
                ],
            ],
            'permissions' => [
                'label' => 'Permissions',
                'items' => [
                    'permissions.view' => 'View permissions catalog',
                    'permissions.manage' => 'Assign permissions to roles',
                    'permissions.export' => 'Update roles seeder from permission assignments',
                ],
            ],
            'portal' => [
                'label' => 'Member Portal',
                'items' => [
                    'portal.dashboard.view' => 'View My Dashboard, My Sales, and portal workspace',
                    'invites.manage' => 'Manage Member Invites (sponsor registration links)',
                    'sponsors.view-tree' => 'View member sponsor hierarchy / My Team',
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
            'sponsors.view-tree',
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
            'admin' => array_values(array_diff($all, [
                'users.delete',
                'settings.manage',
                'issue-reports.view',
                'issue-reports.manage',
            ])),
            'team-admin' => array_values(array_diff($all, [
                'users.delete',
                'settings.manage',
                'crm.records.view-all',
                'issue-reports.view',
                'issue-reports.manage',
            ])),
            'manager' => [
                'portal.dashboard.view',
                'sponsors.view-tree',
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
                'tasks.view',
                'tasks.manage',
                'appointments.view',
                'appointments.manage',
                'calendar.view',
                'calendar.manage',
                'calendar.view-team',
                'crm.records.view-team',
                'landing-pages.view',
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
