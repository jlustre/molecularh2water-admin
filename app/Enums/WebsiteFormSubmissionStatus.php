<?php

namespace App\Enums;

enum WebsiteFormSubmissionStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Scheduled = 'scheduled';
    case Closed = 'closed';
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::Scheduled => 'Scheduled',
            self::Closed => 'Closed',
            self::Spam => 'Spam',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
