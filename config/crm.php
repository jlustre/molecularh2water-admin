<?php



return [



    /*

    |--------------------------------------------------------------------------

    | CRM Module — direct sales (cookware, hydrogen machines, health products)

    |--------------------------------------------------------------------------

    */



    'name' => env('CRM_NAME', 'Field Sales CRM'),



    'default_funnel_slug' => 'sales-funnel',



    'default_stages' => [
        ['name' => 'New Lead', 'slug' => 'new-lead', 'color' => 'slate', 'sort_order' => 1],
        ['name' => 'Contacted', 'slug' => 'contacted', 'color' => 'cyan', 'sort_order' => 2],
        ['name' => 'Qualified as Prospect', 'slug' => 'qualified', 'color' => 'blue', 'sort_order' => 3],
        ['name' => 'Demo Invitation Sent', 'slug' => 'demo-invitation-sent', 'color' => 'indigo', 'sort_order' => 4],
        ['name' => 'Demo Scheduled', 'slug' => 'demo-scheduled', 'color' => 'blue', 'sort_order' => 5],
        ['name' => 'Demo Confirmed', 'slug' => 'demo-confirmed', 'color' => 'indigo', 'sort_order' => 6],
        ['name' => 'Demo Completed', 'slug' => 'demo-completed', 'color' => 'cyan', 'sort_order' => 7],
        ['name' => 'Interested', 'slug' => 'interested', 'color' => 'amber', 'sort_order' => 8],
        ['name' => 'Consultation', 'slug' => 'consultation', 'color' => 'orange', 'sort_order' => 9],
        ['name' => 'Quote Presented', 'slug' => 'quote-presented', 'color' => 'amber', 'sort_order' => 10],
        ['name' => 'Follow-Up', 'slug' => 'follow-up', 'color' => 'amber', 'sort_order' => 11],
        ['name' => 'Decision Pending', 'slug' => 'decision-pending', 'color' => 'orange', 'sort_order' => 12],
        ['name' => 'Ready to Purchase', 'slug' => 'ready-to-purchase', 'color' => 'orange', 'sort_order' => 13],
        ['name' => 'Order Submitted', 'slug' => 'order-submitted', 'color' => 'orange', 'sort_order' => 14],
        ['name' => 'Payment Received', 'slug' => 'payment-received', 'color' => 'emerald', 'sort_order' => 15],
        ['name' => 'Delivery Scheduled', 'slug' => 'delivery-scheduled', 'color' => 'cyan', 'sort_order' => 16],
        ['name' => 'Delivered / Installed', 'slug' => 'delivered-installed', 'color' => 'emerald', 'sort_order' => 17],
        ['name' => 'Customer Orientation', 'slug' => 'customer-orientation', 'color' => 'emerald', 'sort_order' => 18],
        ['name' => 'Referral Requested', 'slug' => 'referral-requested', 'color' => 'blue', 'sort_order' => 19],
        ['name' => 'Closed Won', 'slug' => 'closed-won', 'color' => 'emerald', 'sort_order' => 20, 'is_won' => true],
        ['name' => 'Closed Lost', 'slug' => 'closed-lost', 'color' => 'rose', 'sort_order' => 21, 'is_lost' => true],
    ],

    /*
    |--------------------------------------------------------------------------
    | Portal dashboard — pipeline summary funnels and stage groups
    |--------------------------------------------------------------------------
    */
    'pipeline_summary_funnel_slugs' => [
        'sales-funnel',
        'referral-funnel',
    ],

    'pipeline_summary_groups' => [
        [
            'label' => 'Early',
            'slugs' => ['new-lead', 'contacted', 'qualified'],
        ],
        [
            'label' => 'Demo',
            'slugs' => ['demo-invitation-sent', 'demo-scheduled', 'demo-confirmed', 'demo-completed'],
        ],
        [
            'label' => 'Sales',
            'slugs' => ['interested', 'consultation', 'quote-presented', 'follow-up', 'decision-pending', 'ready-to-purchase'],
        ],
        [
            'label' => 'Fulfillment',
            'slugs' => ['order-submitted', 'payment-received', 'delivery-scheduled', 'delivered-installed'],
        ],
        [
            'label' => 'Close',
            'slugs' => ['customer-orientation', 'referral-requested', 'closed-won', 'closed-lost'],
        ],
        [
            'label' => 'Referrals',
            'slugs' => ['referral-received', 'referral-contacted', 'referral-qualified', 'referral-demo', 'referral-closed'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy funnel stage slug map (pre-2026 pipeline refresh)
    |--------------------------------------------------------------------------
    */
    'legacy_funnel_stage_slug_map' => [
        'show-booked' => 'demo-scheduled',
        'show-completed' => 'demo-completed',
        'order-started' => 'order-submitted',
    ],

    /*
    |--------------------------------------------------------------------------
    | Additional pipeline templates (multi-pipeline CRM)
    |--------------------------------------------------------------------------
    */
    'additional_pipelines' => [
        [
            'slug' => 'recruiting-funnel',
            'name' => 'Recruiting Funnel',
            'description' => 'Prospect, interview, and onboard new distributors.',
            'stages' => [
                ['name' => 'Prospecting', 'slug' => 'prospecting', 'color' => 'slate', 'sort_order' => 1],
                ['name' => 'Interview', 'slug' => 'interview', 'color' => 'cyan', 'sort_order' => 2],
                ['name' => 'Presentation', 'slug' => 'presentation', 'color' => 'blue', 'sort_order' => 3],
                ['name' => 'Follow-Up', 'slug' => 'recruit-follow-up', 'color' => 'amber', 'sort_order' => 4],
                ['name' => 'Registration', 'slug' => 'registration', 'color' => 'indigo', 'sort_order' => 5],
                ['name' => 'Training', 'slug' => 'training', 'color' => 'orange', 'sort_order' => 6],
                ['name' => 'Active Distributor', 'slug' => 'active-distributor', 'color' => 'emerald', 'sort_order' => 7, 'is_won' => true],
            ],
        ],
        [
            'slug' => 'customer-onboarding-funnel',
            'name' => 'Customer Onboarding Funnel',
            'description' => 'Welcome new customers through installation and first follow-up.',
            'stages' => [
                ['name' => 'Welcome', 'slug' => 'onboarding-welcome', 'color' => 'cyan', 'sort_order' => 1],
                ['name' => 'Installation', 'slug' => 'onboarding-installation', 'color' => 'blue', 'sort_order' => 2],
                ['name' => 'Orientation', 'slug' => 'onboarding-orientation', 'color' => 'indigo', 'sort_order' => 3],
                ['name' => 'First Follow-Up', 'slug' => 'onboarding-first-follow-up', 'color' => 'amber', 'sort_order' => 4],
                ['name' => 'Active Customer', 'slug' => 'active-customer', 'color' => 'emerald', 'sort_order' => 5, 'is_won' => true],
            ],
        ],
        [
            'slug' => 'referral-funnel',
            'name' => 'Referral Funnel',
            'description' => 'Track referred prospects from introduction to close.',
            'stages' => [
                ['name' => 'Referral Received', 'slug' => 'referral-received', 'color' => 'slate', 'sort_order' => 1],
                ['name' => 'Contacted', 'slug' => 'referral-contacted', 'color' => 'cyan', 'sort_order' => 2],
                ['name' => 'Qualified', 'slug' => 'referral-qualified', 'color' => 'blue', 'sort_order' => 3],
                ['name' => 'Demo', 'slug' => 'referral-demo', 'color' => 'indigo', 'sort_order' => 4],
                ['name' => 'Closed', 'slug' => 'referral-closed', 'color' => 'emerald', 'sort_order' => 5, 'is_won' => true],
            ],
        ],
        [
            'slug' => 'after-sales-funnel',
            'name' => 'After-Sales Service Funnel',
            'description' => 'Warranty, maintenance, upgrades, and VIP customer care.',
            'stages' => [
                ['name' => 'Warranty Registration', 'slug' => 'warranty-registration', 'color' => 'slate', 'sort_order' => 1],
                ['name' => 'Installation Complete', 'slug' => 'installation-complete', 'color' => 'cyan', 'sort_order' => 2],
                ['name' => '30-Day Follow-Up', 'slug' => 'thirty-day-follow-up', 'color' => 'blue', 'sort_order' => 3],
                ['name' => '90-Day Satisfaction Check', 'slug' => 'ninety-day-check', 'color' => 'indigo', 'sort_order' => 4],
                ['name' => 'Annual Maintenance Reminder', 'slug' => 'annual-maintenance', 'color' => 'amber', 'sort_order' => 5],
                ['name' => 'Referral Campaign', 'slug' => 'referral-campaign', 'color' => 'orange', 'sort_order' => 6],
                ['name' => 'Product Upgrade Campaign', 'slug' => 'upgrade-campaign', 'color' => 'orange', 'sort_order' => 7],
                ['name' => 'Cross-Sell Opportunity', 'slug' => 'cross-sell', 'color' => 'amber', 'sort_order' => 8],
                ['name' => 'VIP Customer', 'slug' => 'vip-customer', 'color' => 'emerald', 'sort_order' => 9],
                ['name' => 'Brand Ambassador', 'slug' => 'brand-ambassador', 'color' => 'emerald', 'sort_order' => 10, 'is_won' => true],
            ],
        ],
        [
            'slug' => 'corporate-sales-funnel',
            'name' => 'Corporate Sales Funnel',
            'description' => 'B2B and corporate account pipeline.',
            'stages' => [
                ['name' => 'Inquiry', 'slug' => 'corporate-inquiry', 'color' => 'slate', 'sort_order' => 1],
                ['name' => 'Meeting', 'slug' => 'corporate-meeting', 'color' => 'cyan', 'sort_order' => 2],
                ['name' => 'Proposal', 'slug' => 'corporate-proposal', 'color' => 'blue', 'sort_order' => 3],
                ['name' => 'Negotiation', 'slug' => 'corporate-negotiation', 'color' => 'amber', 'sort_order' => 4],
                ['name' => 'Closed', 'slug' => 'corporate-closed', 'color' => 'emerald', 'sort_order' => 5, 'is_won' => true],
                ['name' => 'Closed Lost', 'slug' => 'corporate-closed-lost', 'color' => 'rose', 'sort_order' => 6, 'is_lost' => true],
            ],
        ],
    ],

    'demo_stage_slugs' => [
        'demo-scheduled',
        'demo-confirmed',
        'demo-completed',
    ],

    'demo_outcome_stage_map' => [
        'interested' => 'interested',
        'not_interested' => 'closed-lost',
        'sold' => 'ready-to-purchase',
        'rescheduled' => 'demo-scheduled',
        'pending' => 'demo-completed',
    ],

    'default_product_categories' => [
        ['name' => 'H2 Machines', 'kind' => 'product', 'sort_order' => 1],
        ['name' => 'Cookware', 'kind' => 'product', 'sort_order' => 2],
        ['name' => 'Accessories', 'kind' => 'product', 'sort_order' => 3],
        ['name' => 'Services', 'kind' => 'product', 'sort_order' => 4],
        ['name' => 'Gifts', 'kind' => 'gift', 'sort_order' => 5],
    ],

    'default_products' => [
        [
            'sku' => 'H2-ULTRA-PRO',
            'name' => 'Molecular H2 Ultra Pro',
            'category' => 'H2 Machines',
            'description' => 'Premium countertop hydrogen water generator with dual filtration.',
            'unit_price' => 3499.00,
            'sort_order' => 1,
        ],
        [
            'sku' => 'H2-COMPACT',
            'name' => 'Molecular H2 Compact',
            'category' => 'H2 Machines',
            'description' => 'Space-saving hydrogen water unit for apartments and offices.',
            'unit_price' => 2199.00,
            'sort_order' => 2,
        ],
        [
            'sku' => 'COOKWARE-SET',
            'name' => 'Titanium Cookware Set (12pc)',
            'category' => 'Cookware',
            'description' => 'Full titanium non-toxic cookware collection with lifetime warranty.',
            'unit_price' => 1899.00,
            'sort_order' => 3,
        ],
        [
            'sku' => 'FILTER-ANNUAL',
            'name' => 'Annual Filter Replacement Kit',
            'category' => 'Accessories',
            'description' => 'Replacement filters for H2 Ultra Pro and Compact models.',
            'unit_price' => 149.00,
            'sort_order' => 4,
        ],
        [
            'sku' => 'INSTALL-SVC',
            'name' => 'Professional Installation',
            'category' => 'Services',
            'description' => 'On-site installation and water quality calibration.',
            'unit_price' => 199.00,
            'inventory_quantity' => 50,
            'sort_order' => 5,
        ],
        [
            'sku' => 'GIFT-WELCOME',
            'name' => 'Welcome Gift Basket',
            'kind' => 'gift',
            'category' => 'Gifts',
            'description' => 'Starter gift bundle for new customers.',
            'unit_price' => 75.00,
            'inventory_quantity' => 25,
            'sort_order' => 10,
        ],
        [
            'sku' => 'GIFT-REFERRAL',
            'name' => 'Referral Thank-You Gift',
            'kind' => 'gift',
            'category' => 'Gifts',
            'description' => 'Gift given for successful referrals.',
            'unit_price' => 50.00,
            'inventory_quantity' => 40,
            'sort_order' => 11,
        ],
    ],

    'dashboard_stage_slugs' => [
        'pending_quotes' => ['quote-presented'],
        'pending_orders' => ['order-submitted', 'ready-to-purchase', 'payment-received'],
        'pending_delivery' => ['delivery-scheduled', 'payment-received'],
    ],

    'closed_won_stage_slug' => 'closed-won',

    /*
    |--------------------------------------------------------------------------
    | Sales funnel — Lead → Prospect conversion
    |--------------------------------------------------------------------------
    |
    | Contacts on earlier sales-funnel stages stay Leads. Moving to this stage
    | (or any later stage) converts them to Prospects.
    |
    */
    'prospect_conversion_stage_slug' => 'qualified',

    'after_sales_funnel_slug' => 'after-sales-funnel',
    'after_sales_entry_stage' => 'warranty-registration',

    'referral_funnel_slug' => 'referral-funnel',
    'referral_entry_stage' => 'referral-received',

    'referral_reward_types' => [
        'gift_card' => 'Gift Card',
        'account_credit' => 'Account Credit',
        'product_discount' => 'Product Discount',
        'cash_bonus' => 'Cash Bonus',
        'points' => 'Loyalty Points',
    ],

    'delivery_checklist' => [
        'product_verified' => 'Product verified against order',
        'packaging_inspected' => 'Packaging inspected',
        'customer_signature' => 'Customer signature obtained',
        'delivery_photos' => 'Delivery photos taken',
    ],

    'installation_checklist' => [
        'unit_installed' => 'Unit installed and leveled',
        'water_line_connected' => 'Water line connected',
        'filters_installed' => 'Filters installed',
        'calibration_completed' => 'Calibration completed',
        'customer_walkthrough' => 'Customer walkthrough done',
    ],



    'lead_sources' => [

        'Website',

        'Landing Page',

        'Referral',

        'Social Media',

        'Cooking Show',

        'Water Awareness Show',

        'Cold Call',

        'Warranty Registration',

        'Other',

    ],



    'default_lost_reasons' => [

        ['name' => 'No Response', 'slug' => 'no-response'],

        ['name' => 'Could Not Contact', 'slug' => 'could-not-contact'],

        ['name' => 'Demo Cancelled', 'slug' => 'demo-cancelled'],

        ['name' => 'Demo No Show', 'slug' => 'demo-no-show'],

        ['name' => 'Not Interested', 'slug' => 'not-interested'],

        ['name' => 'Price Too High', 'slug' => 'price-too-high'],

        ['name' => 'Financial Constraints', 'slug' => 'financial-constraints'],

        ['name' => 'Wants More Time', 'slug' => 'wants-more-time'],

        ['name' => 'Bought Competitor Product', 'slug' => 'bought-competitor-product'],

        ['name' => 'Spouse/Partner Declined', 'slug' => 'spouse-partner-declined'],

        ['name' => "Product Doesn't Fit Needs", 'slug' => 'product-doesnt-fit-needs'],

        ['name' => 'Moving', 'slug' => 'moving'],

        ['name' => 'Duplicate Lead', 'slug' => 'duplicate-lead'],

        ['name' => 'Invalid Contact Information', 'slug' => 'invalid-contact-information'],

        ['name' => 'Other', 'slug' => 'other', 'requires_detail' => true],

    ],



    'activity_types' => [

        ['name' => 'Phone Call', 'slug' => 'phone-call', 'icon' => 'phone'],

        ['name' => 'Email', 'slug' => 'email', 'icon' => 'mail'],

        ['name' => 'Text Message', 'slug' => 'text-message', 'icon' => 'message-square'],

        ['name' => 'Video Call', 'slug' => 'video-call', 'icon' => 'video'],

        ['name' => 'Cooking Show', 'slug' => 'cooking-show', 'icon' => 'utensils'],

        ['name' => 'Water Awareness Show', 'slug' => 'water-awareness-show', 'icon' => 'droplet'],

        ['name' => 'Home Demo', 'slug' => 'home-demo', 'icon' => 'home'],

        ['name' => 'Product Demo', 'slug' => 'product-demo', 'icon' => 'package'],

        ['name' => 'Follow-Up', 'slug' => 'follow-up', 'icon' => 'clock'],

        ['name' => 'Note', 'slug' => 'note', 'icon' => 'sticky-note'],

        ['name' => 'Referral', 'slug' => 'referral', 'icon' => 'user-plus'],

        ['name' => 'Order Placed', 'slug' => 'order-placed', 'icon' => 'shopping-cart'],

    ],



    'legacy_inactive_activity_slugs' => [

        'policy-review',

        'application-submission',

        'zoom-meeting',

        'presentation',

        'webinar',

    ],



    'pagination' => [

        'leads' => 15,

        'activities' => 20,

        'tasks' => 20,

    ],



    'statuses' => [

        'new' => 'New',

        'contacting' => 'Contacting',

        'active' => 'Active',

        'engaged' => 'Engaged',

        'attended-demo' => 'Attended Demo',

        'considering' => 'Considering',

        'negotiating' => 'Negotiating',

        'ready-to-buy' => 'Ready to Buy',

        'customer' => 'Customer',

        'inactive' => 'Inactive',

    ],



    'legacy_lead_status_map' => [

        'contacted' => 'contacting',

        'qualified' => 'active',

        'nurturing' => 'considering',

        'converted' => 'customer',

        'lost' => 'inactive',

    ],



    'import' => [

        'max_rows' => 500,

    ],



    'stage_colors' => [

        'slate' => 'Slate',

        'cyan' => 'Cyan',

        'blue' => 'Blue',

        'indigo' => 'Indigo',

        'amber' => 'Amber',

        'orange' => 'Orange',

        'emerald' => 'Emerald',

        'rose' => 'Rose',

    ],



    'activity_outcomes' => [

        'connected' => 'Connected',

        'voicemail' => 'Left Voicemail',

        'no_answer' => 'No Answer',

        'interested' => 'Interested',

        'not_interested' => 'Not Interested',

        'follow_up_needed' => 'Follow-Up Needed',

        'completed' => 'Completed',

        'purchased' => 'Purchased',

        'booked_show' => 'Booked Show',

    ],



    'meeting_types' => [

        'in_person' => 'In Person',

        'phone' => 'Phone Call',

        'video' => 'Video Call',

        'home_demo' => 'Home Demo',

        'show_venue' => 'Show Venue',

    ],



    'appointment_statuses' => [

        'scheduled' => 'Scheduled',

        'confirmed' => 'Confirmed',

        'completed' => 'Completed',

        'cancelled' => 'Cancelled',

        'no_show' => 'No Show',

    ],



    'capture' => [

        'honeypot_field' => 'company_website',

        'default_lifecycle' => 'prospect',

        'default_temperature' => 'warm',

    ],



    'landing_pages' => [

        'assignment' => env('CRM_LANDING_ASSIGNMENT', 'round_robin'),

        'default_form_settings' => [

            'assignment' => 'round_robin',

            'lifecycle' => 'prospect',

        ],

        'default_form_fields' => [

            ['name' => 'first_name', 'label' => 'First Name', 'type' => 'text', 'required' => true],

            ['name' => 'last_name', 'label' => 'Last Name', 'type' => 'text', 'required' => false],

            ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],

            ['name' => 'phone', 'label' => 'Phone', 'type' => 'tel', 'required' => false],

            ['name' => 'city', 'label' => 'City', 'type' => 'text', 'required' => false],

            ['name' => 'interested_in', 'label' => 'Interested In', 'type' => 'text', 'required' => false, 'placeholder' => 'Cookware, H2 machine, health products'],

            ['name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'required' => false],

        ],

    ],



    /*

    |--------------------------------------------------------------------------

    | Record visibility

    |--------------------------------------------------------------------------

    |

    | CRM users only see leads, prospects, clients, tasks, activities, and

    | appointments assigned to them. Super Admin and Admin roles receive the

    | crm.records.view-all permission to oversee all records.

    |

    */



    'visibility' => [

        'view_all_permission' => 'crm.records.view-all',

    ],



    'dashboard_cache_ttl' => (int) env('CRM_DASHBOARD_CACHE_TTL', 300),



    'queue' => [

        'notifications' => env('CRM_NOTIFICATIONS_QUEUE', 'default'),

    ],



    'automation' => [

        'enabled' => env('CRM_AUTOMATION_ENABLED', true),

        'sync' => env('CRM_AUTOMATION_SYNC', false),

        'queue' => env('CRM_AUTOMATION_QUEUE', 'default'),



        'rules' => [

            'demonstration.scheduled' => [

                ['action' => 'calendar_event', 'event_type' => 'home-demo', 'title' => 'Demo: {{lead_name}}'],

                ['action' => 'notify_assignee', 'notification' => 'demo_scheduled'],

                ['action' => 'move_stage', 'stage_slug' => 'demo-scheduled'],

            ],

            'demonstration.completed' => [

                ['action' => 'task', 'title' => 'Post-demo follow-up: {{lead_name}}', 'due_days' => 1, 'priority' => 'high'],

            ],

            'order.paid' => [

                ['action' => 'task', 'title' => 'Schedule delivery for {{order_number}}', 'due_days' => 2, 'priority' => 'high'],

            ],

            'delivery.completed' => [

                ['action' => 'task', 'title' => 'Schedule customer orientation: {{lead_name}}', 'due_days' => 3],

            ],

            'stage.moved' => [

                'customer-orientation' => [

                    ['action' => 'task', 'title' => '30-day follow-up: {{lead_name}}', 'due_days' => 30],

                ],

                'referral-requested' => [

                    ['action' => 'task', 'title' => 'Referral campaign: {{lead_name}}', 'due_days' => 7],

                ],

            ],

        ],



        'sequences' => [

            'prospect_captured' => 'new-prospect-nurture',

            'demonstration.scheduled' => 'demo-reminder-sequence',

        ],

    ],



    'prospect_profile_tags' => [

        '25+ years old',

        'married',

        'w/ Dep. Children',

        'homeowner',

        'business minded',

        'Health conscious',

    ],



    'prospect_best_times_to_contact' => [

        'morning' => 'Morning (8am–12pm)',

        'afternoon' => 'Afternoon (12pm–5pm)',

        'evening' => 'Evening (5pm–8pm)',

        'weekdays' => 'Weekdays',

        'weekends' => 'Weekends',

        'anytime' => 'Anytime',

    ],



];

