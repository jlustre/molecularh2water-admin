<?php

namespace App\Enums;

enum IssueReportSite: string
{
    case Frontend = 'frontend';
    case Backend = 'backend';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Frontend => 'Public website',
            self::Backend => 'Admin / member portal',
            self::Both => 'Both sites',
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
