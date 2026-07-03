<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Invite-only registration
    |--------------------------------------------------------------------------
    |
    | When enabled, new accounts require a one-time invite code from a sponsor.
    |
    */
    'invite_only' => (bool) env('REGISTRATION_INVITE_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Invite expiration (days)
    |--------------------------------------------------------------------------
    |
    | Generated invites expire after this many days. Set to 0 for no expiration.
    |
    */
    'invite_ttl_days' => (int) env('REGISTRATION_INVITE_TTL_DAYS', 30),

];
