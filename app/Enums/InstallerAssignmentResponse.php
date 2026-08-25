<?php

namespace App\Enums;

enum InstallerAssignmentResponse: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting reply',
            self::Accepted => 'Accepted',
            self::Rejected => 'Declined',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-800 ring-1 ring-amber-200',
            self::Accepted => 'bg-emerald-600 text-white',
            self::Rejected => 'bg-red-600 text-white',
        };
    }
}
