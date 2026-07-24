<?php

use App\Support\Portal\Dashboard\Providers\NetworkSectionProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Portal dashboard section providers
    |--------------------------------------------------------------------------
    |
    | Register section providers to extend the associate portal dashboard.
    | Each provider returns stat cards relevant to the authenticated user.
    |
    */

    'dashboard_section_providers' => [
        NetworkSectionProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Phone call reasons (schedule modal)
    |--------------------------------------------------------------------------
    */

    'phone_call_reasons' => [
        'shared' => [
            ['value' => 'general_follow_up', 'label' => 'General follow-up'],
            ['value' => 'left_voicemail', 'label' => 'Left voicemail / no answer'],
        ],
        'prospect' => [
            ['value' => 'invite_cooking_show', 'label' => 'Invite to cooking show'],
            ['value' => 'invite_water_awareness', 'label' => 'Invite to water awareness show'],
            ['value' => 'schedule_home_demo', 'label' => 'Schedule home demo'],
            ['value' => 'schedule_product_demo', 'label' => 'Schedule product demo'],
            ['value' => 'post_demo_follow_up', 'label' => 'Post-demo follow-up'],
            ['value' => 'qualify_interest', 'label' => 'Qualify interest & needs'],
            ['value' => 'share_h2_benefits', 'label' => 'Share H2 water benefits'],
            ['value' => 'quote_pricing', 'label' => 'Quote / pricing discussion'],
            ['value' => 'decision_check_in', 'label' => 'Decision check-in'],
            ['value' => 're_engage_cold_lead', 'label' => 'Re-engage cold lead'],
            ['value' => 'referral_request', 'label' => 'Request referrals'],
        ],
        'customer' => [
            ['value' => 'onboarding_check_in', 'label' => 'Customer onboarding check-in'],
            ['value' => 'order_follow_up', 'label' => 'Order follow-up'],
            ['value' => 'delivery_coordination', 'label' => 'Delivery coordination'],
            ['value' => 'thirty_day_check_in', 'label' => '30-day wellness check-in'],
            ['value' => 'machine_usage_support', 'label' => 'Machine usage / support'],
            ['value' => 'reorder_supplies', 'label' => 'Reorder filters or supplies'],
            ['value' => 'warranty_support', 'label' => 'Warranty or service question'],
            ['value' => 'referral_request', 'label' => 'Request referrals'],
            ['value' => 'upsell_accessories', 'label' => 'Upsell accessories or upgrades'],
        ],
        'team' => [
            ['value' => 'team_training', 'label' => 'Team training / coaching'],
            ['value' => 'share_registration_invite', 'label' => 'Share registration invite'],
            ['value' => 'pipeline_review', 'label' => 'Pipeline review'],
            ['value' => 'show_prep', 'label' => 'Cooking / awareness show prep'],
            ['value' => 'accountability_check_in', 'label' => 'Accountability check-in'],
            ['value' => 'welcome_new_member', 'label' => 'Welcome new team member'],
            ['value' => 'business_growth_plan', 'label' => 'Business growth planning'],
        ],
        'other_contact' => [
            ['value' => 'vendor_supplier', 'label' => 'Vendor / supplier'],
            ['value' => 'corporate_partner', 'label' => 'Corporate partner'],
            ['value' => 'personal_contact', 'label' => 'Personal contact'],
            ['value' => 'return_missed_call', 'label' => 'Return missed call'],
        ],
        'other' => [
            ['value' => 'other', 'label' => 'Other (describe in notes)'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Phone call results (completion modal — synced to CRM activities)
    |--------------------------------------------------------------------------
    */

    'phone_call_results' => [
        'connected' => 'Connected',
        'voicemail' => 'Left Voicemail',
        'no_answer' => 'No Answer',
        'interested' => 'Interested',
        'not_interested' => 'Not Interested',
        'follow_up_needed' => 'Follow-Up Needed',
        'booked_show' => 'Booked Show',
        'completed' => 'Completed',
    ],

    /*
    |--------------------------------------------------------------------------
    | Online demo settings key (stored in settings table)
    |--------------------------------------------------------------------------
    */

    'online_demo_link_setting' => 'portal.online_demo_link',

    /*
    |--------------------------------------------------------------------------
    | Meeting formats and recurrence (schedule modal)
    |--------------------------------------------------------------------------
    */

    'meeting_formats' => [
        ['value' => 'in_person', 'label' => 'In person'],
        ['value' => 'online', 'label' => 'Online / Zoom'],
    ],

    'meeting_recurrence' => [
        ['value' => 'none', 'label' => 'Does not repeat'],
        ['value' => 'weekly', 'label' => 'Weekly'],
        ['value' => 'biweekly', 'label' => 'Every 2 weeks'],
        ['value' => 'monthly', 'label' => 'Monthly'],
    ],

    'meeting_recurrence_counts' => [4, 8, 12, 26],

    'meeting_invitee_groups' => [
        ['value' => '', 'label' => 'None'],
        ['value' => 'team_members', 'label' => 'All team members'],
        ['value' => 'managers', 'label' => 'All managers'],
        ['value' => 'office_staff', 'label' => 'All office staff'],
        ['value' => 'consultants', 'label' => 'All consultants'],
    ],

];
