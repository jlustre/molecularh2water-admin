<?php

namespace App\Enums\Crm;

enum DemonstrationType: string
{
    case Home = 'home';
    case Office = 'office';
    case Zoom = 'zoom';
    case Group = 'group';
    case Event = 'event';
    case TradeShow = 'trade_show';

    public function label(): string
    {
        return match ($this) {
            self::Home => 'Home Demo',
            self::Office => 'Office Demo',
            self::Zoom => 'Zoom Demo',
            self::Group => 'Group Presentation',
            self::Event => 'Event Demo',
            self::TradeShow => 'Trade Show Demo',
        };
    }

    public function isOnline(): bool
    {
        return $this === self::Zoom;
    }
}
