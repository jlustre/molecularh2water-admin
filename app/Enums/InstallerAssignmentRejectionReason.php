<?php

namespace App\Enums;

enum InstallerAssignmentRejectionReason: string
{
    case ScheduleConflict = 'schedule_conflict';
    case OutsideArea = 'outside_area';
    case TooComplex = 'too_complex';
    case MissingInformation = 'missing_information';
    case Unavailable = 'unavailable';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ScheduleConflict => 'Schedule conflict',
            self::OutsideArea => 'Outside my service area',
            self::TooComplex => 'Job looks too complex',
            self::MissingInformation => 'Missing information or photos',
            self::Unavailable => 'Not available for this date',
            self::Other => 'Other',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $reason) => [$reason->value => $reason->label()])
            ->all();
    }
}
