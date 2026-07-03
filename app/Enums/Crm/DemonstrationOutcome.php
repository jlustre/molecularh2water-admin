<?php

namespace App\Enums\Crm;

enum DemonstrationOutcome: string
{
    case Interested = 'interested';
    case NotInterested = 'not_interested';
    case Rescheduled = 'rescheduled';
    case Pending = 'pending';
    case Sold = 'sold';

    public function label(): string
    {
        return match ($this) {
            self::Interested => 'Interested',
            self::NotInterested => 'Not Interested',
            self::Rescheduled => 'Rescheduled',
            self::Pending => 'Pending Decision',
            self::Sold => 'Sold',
        };
    }
}
