<?php

namespace App\Enums\Crm;

enum EngagementType: string
{
    case Customer = 'C';
    case Both = 'B';
    case Recruit = 'R';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Customer',
            self::Both => 'Customer & Recruit',
            self::Recruit => 'Recruit',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }
}
