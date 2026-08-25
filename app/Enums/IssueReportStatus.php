<?php

namespace App\Enums;

enum IssueReportStatus: string
{
    case New = 'new';
    case Acknowledged = 'acknowledged';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case WontFix = 'wont_fix';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Acknowledged => 'Acknowledged',
            self::InProgress => 'In Progress',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
            self::WontFix => "Won't Fix",
        };
    }

    public function reporterMessage(): string
    {
        return match ($this) {
            self::New => 'We received your report and it is in the review queue.',
            self::Acknowledged => 'A super-admin has reviewed your report and acknowledged the issue.',
            self::InProgress => 'The team is actively working on this issue.',
            self::Resolved => 'This issue has been marked as resolved.',
            self::Closed => 'This issue has been closed.',
            self::WontFix => 'This issue will not be changed at this time.',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::New, self::Acknowledged, self::InProgress], true);
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::New => 'bg-amber-50 text-amber-700',
            self::Acknowledged => 'bg-sky-50 text-sky-700',
            self::InProgress => 'bg-indigo-50 text-indigo-700',
            self::Resolved => 'bg-emerald-50 text-emerald-700',
            self::Closed => 'bg-slate-100 text-slate-600',
            self::WontFix => 'bg-zinc-100 text-zinc-600',
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
