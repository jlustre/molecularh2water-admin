<?php

namespace App\Enums;

enum BusinessLine: string
{
    case Hcc = 'hcc';
    case H2s = 'h2s';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Hcc => 'HappyCookingCo',
            self::H2s => 'H2S',
            self::Both => 'Both',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Hcc => 'HCC',
            self::H2s => 'H2S',
            self::Both => 'Both',
        };
    }

    public function color(): string
    {
        return config('business.lines.'.$this->value.'.color', 'slate');
    }

    /**
     * @return list<self>
     */
    public static function assignableCases(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $line) => $line !== self::Both,
        ));
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
