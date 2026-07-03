<?php

namespace App\Enums\Crm;

enum LeadTemperature: string
{
    case Cold = 'cold';
    case Warm = 'warm';
    case Hot = 'hot';

    public function label(): string
    {
        return match ($this) {
            self::Cold => 'Cold',
            self::Warm => 'Warm',
            self::Hot => 'Hot',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Cold => 'slate',
            self::Warm => 'amber',
            self::Hot => 'rose',
        };
    }
}
