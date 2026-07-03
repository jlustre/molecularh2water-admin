<?php

namespace App\Enums\Crm;

enum LeadLifecycle: string
{
    case Lead = 'lead';
    case Prospect = 'prospect';
    case Client = 'client';
    case Recruit = 'recruit';

    public function label(): string
    {
        return match ($this) {
            self::Lead => 'Lead',
            self::Prospect => 'Prospect',
            self::Client => 'Customer',
            self::Recruit => 'Recruit',
        };
    }
}
