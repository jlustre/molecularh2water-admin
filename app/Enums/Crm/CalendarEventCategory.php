<?php

namespace App\Enums\Crm;

enum CalendarEventCategory: string
{
    case Show = 'show';
    case Demo = 'demo';
    case FollowUp = 'follow-up';
    case Meeting = 'meeting';
    case Internal = 'internal';

    public function label(): string
    {
        return match ($this) {
            self::Show => 'Shows',
            self::Demo => 'Demos',
            self::FollowUp => 'Follow-ups',
            self::Meeting => 'Meetings',
            self::Internal => 'Internal',
        };
    }

    public function isShow(): bool
    {
        return $this === self::Show;
    }
}
