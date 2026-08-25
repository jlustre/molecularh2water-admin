<?php

namespace App\Enums;

enum IssueReportSource: string
{
    case PublicWebsite = 'public_website';
    case Portal = 'portal';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::PublicWebsite => 'Public website',
            self::Portal => 'Member portal',
            self::Admin => 'Admin console',
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
