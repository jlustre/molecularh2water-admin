<?php

return [

    'default_timezone' => env('CRM_CALENDAR_TIMEZONE', config('app.timezone', 'UTC')),

    'show_default_duration_hours' => 2,

    'reminder_presets' => [
        5 => '5 minutes before',
        15 => '15 minutes before',
        30 => '30 minutes before',
        60 => '1 hour before',
        1440 => '1 day before',
    ],

    'default_reminders' => [15, 60],

    /*
    |--------------------------------------------------------------------------
    | Recurrence (schedule event modal)
    |--------------------------------------------------------------------------
    |
    | Occurrences are materialized as linked calendar_events rows (same approach
    | as portal meetings) so month/week grids need no virtual expansion.
    |
    */
    'recurrence' => [
        ['value' => 'none', 'label' => 'Does not repeat'],
        ['value' => 'daily', 'label' => 'Daily'],
        ['value' => 'weekly', 'label' => 'Weekly'],
        ['value' => 'biweekly', 'label' => 'Every 2 weeks'],
        ['value' => 'monthly', 'label' => 'Monthly'],
    ],

    'recurrence_counts' => [4, 8, 12, 26, 52],

    /*
    |--------------------------------------------------------------------------
    | Event types — direct sales (cookware, hydrogen machines, health products)
    |--------------------------------------------------------------------------
    |
    | category: show | demo | follow-up | meeting | internal
    |
    */
    'event_types' => [
        ['name' => 'Cooking Show', 'slug' => 'cooking-show', 'category' => 'show', 'color' => 'orange', 'icon' => 'utensils', 'activity_type_slug' => 'cooking-show', 'sort_order' => 1],
        ['name' => 'Water Awareness Show', 'slug' => 'water-awareness-show', 'category' => 'show', 'color' => 'cyan', 'icon' => 'droplet', 'activity_type_slug' => 'water-awareness-show', 'sort_order' => 2],
        ['name' => 'Home Demo', 'slug' => 'home-demo', 'category' => 'demo', 'color' => 'violet', 'icon' => 'home', 'activity_type_slug' => 'home-demo', 'sort_order' => 10],
        ['name' => 'Product Demo', 'slug' => 'product-demo', 'category' => 'demo', 'color' => 'purple', 'icon' => 'package', 'activity_type_slug' => 'product-demo', 'sort_order' => 11],
        ['name' => 'Follow-Up Call', 'slug' => 'follow-up', 'category' => 'follow-up', 'color' => 'amber', 'icon' => 'clock', 'activity_type_slug' => 'follow-up', 'sort_order' => 20],
        ['name' => 'Post-Show Follow-Up', 'slug' => 'post-show-follow-up', 'category' => 'follow-up', 'color' => 'yellow', 'icon' => 'phone-forwarded', 'activity_type_slug' => 'follow-up', 'sort_order' => 21],
        ['name' => 'Phone Call', 'slug' => 'phone-call', 'category' => 'meeting', 'color' => 'blue', 'icon' => 'phone', 'activity_type_slug' => 'phone-call', 'sort_order' => 30],
        ['name' => 'In-Person Meeting', 'slug' => 'in-person-meeting', 'category' => 'meeting', 'color' => 'indigo', 'icon' => 'map-pin', 'activity_type_slug' => 'home-demo', 'sort_order' => 31],
        ['name' => 'Online Meeting', 'slug' => 'zoom-meeting', 'category' => 'meeting', 'color' => 'blue', 'icon' => 'video', 'activity_type_slug' => 'phone-call', 'sort_order' => 32],
        ['name' => 'Team Training', 'slug' => 'team-training', 'category' => 'internal', 'color' => 'teal', 'icon' => 'graduation-cap', 'creates_activity' => false, 'sort_order' => 40],
        ['name' => 'Personal Task', 'slug' => 'personal-task', 'category' => 'internal', 'color' => 'slate', 'icon' => 'check-square', 'creates_activity' => false, 'sort_order' => 41],
        ['name' => 'Deadline', 'slug' => 'deadline', 'category' => 'internal', 'color' => 'red', 'icon' => 'flag', 'creates_activity' => false, 'sort_order' => 42],
    ],

    'legacy_inactive_slugs' => [
        'policy-review',
        'application-review',
        'client-onboarding',
        'presentation',
        'webinar',
    ],

    'funnel_stage_actions' => [
        'contacted' => ['event_type' => 'follow-up', 'title' => 'Follow-up call'],
        'qualified' => ['event_type' => 'follow-up', 'title' => 'Qualification follow-up'],
        'demo-invitation-sent' => ['event_type' => 'product-demo', 'title' => 'Demo invitation follow-up'],
        'demo-scheduled' => ['event_type' => 'home-demo', 'title' => 'Scheduled demo'],
        'demo-confirmed' => ['event_type' => 'home-demo', 'title' => 'Demo confirmation call'],
        'demo-completed' => ['event_type' => 'post-show-follow-up', 'title' => 'Post-demo follow-up'],
        'consultation' => ['event_type' => 'in-person-meeting', 'title' => 'Consultation meeting'],
        'quote-presented' => ['event_type' => 'follow-up', 'title' => 'Quote follow-up'],
        'follow-up' => ['event_type' => 'follow-up', 'title' => 'Follow-up call'],
        'decision-pending' => ['event_type' => 'follow-up', 'title' => 'Decision check-in'],
        'ready-to-purchase' => ['event_type' => 'follow-up', 'title' => 'Purchase follow-up'],
        'order-submitted' => ['event_type' => 'follow-up', 'title' => 'Order follow-up'],
        'payment-received' => ['event_type' => 'follow-up', 'title' => 'Delivery coordination'],
        'delivery-scheduled' => ['event_type' => 'in-person-meeting', 'title' => 'Delivery appointment'],
        'delivered-installed' => ['event_type' => 'home-demo', 'title' => 'Installation check-in'],
        'customer-orientation' => ['event_type' => 'in-person-meeting', 'title' => 'Customer orientation'],
        'referral-requested' => ['event_type' => 'follow-up', 'title' => 'Referral follow-up'],
    ],

    'type_colors' => [
        'slate' => 'bg-slate-100 text-slate-800 border-slate-200',
        'cyan' => 'bg-cyan-100 text-cyan-800 border-cyan-200',
        'blue' => 'bg-blue-100 text-blue-800 border-blue-200',
        'indigo' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
        'violet' => 'bg-violet-100 text-violet-800 border-violet-200',
        'purple' => 'bg-purple-100 text-purple-800 border-purple-200',
        'amber' => 'bg-amber-100 text-amber-800 border-amber-200',
        'yellow' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'orange' => 'bg-orange-100 text-orange-800 border-orange-200',
        'emerald' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'teal' => 'bg-teal-100 text-teal-800 border-teal-200',
        'rose' => 'bg-rose-100 text-rose-800 border-rose-200',
        'red' => 'bg-red-100 text-red-800 border-red-200',
    ],

];
