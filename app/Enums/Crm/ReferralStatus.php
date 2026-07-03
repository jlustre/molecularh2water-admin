<?php

namespace App\Enums\Crm;

enum ReferralStatus: string
{
    case Pending = 'pending';
    case Contacted = 'contacted';
    case Converted = 'converted';
    case Rewarded = 'rewarded';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Contacted => 'Contacted',
            self::Converted => 'Converted',
            self::Rewarded => 'Rewarded',
            self::Declined => 'Declined',
        };
    }
}
