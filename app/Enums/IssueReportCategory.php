<?php

namespace App\Enums;

enum IssueReportCategory: string
{
    case Bug = 'bug';
    case Error = 'error';
    case UiUx = 'ui_ux';
    case Content = 'content';
    case Performance = 'performance';
    case AccountAccess = 'account_access';
    case Data = 'data';
    case Email = 'email';
    case Security = 'security';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Bug => 'Bug',
            self::Error => 'Error / crash',
            self::UiUx => 'Layout / design',
            self::Content => 'Content / copy',
            self::Performance => 'Performance',
            self::AccountAccess => 'Account / login',
            self::Data => 'Missing or incorrect data',
            self::Email => 'Email / notifications',
            self::Security => 'Security concern',
            self::Other => 'Other',
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
