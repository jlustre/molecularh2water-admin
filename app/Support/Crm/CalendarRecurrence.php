<?php

namespace App\Support\Crm;

use Carbon\Carbon;
use Illuminate\Support\Str;

class CalendarRecurrence
{
    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return config('calendar.recurrence', [
            ['value' => 'none', 'label' => 'Does not repeat'],
            ['value' => 'daily', 'label' => 'Daily'],
            ['value' => 'weekly', 'label' => 'Weekly'],
            ['value' => 'biweekly', 'label' => 'Every 2 weeks'],
            ['value' => 'monthly', 'label' => 'Monthly'],
        ]);
    }

    /**
     * @return list<int>
     */
    public static function counts(): array
    {
        return config('calendar.recurrence_counts', [4, 8, 12, 26, 52]);
    }

    /**
     * @return list<string>
     */
    public static function ruleValues(): array
    {
        return collect(static::options())->pluck('value')->all();
    }

    public static function labelFor(string $rule): string
    {
        return collect(static::options())->firstWhere('value', $rule)['label'] ?? 'Does not repeat';
    }

    public static function newGroupId(): string
    {
        return (string) Str::uuid();
    }

    /**
     * @return list<array{0: Carbon, 1: Carbon}>
     */
    public static function buildOccurrences(
        Carbon $startAt,
        Carbon $endAt,
        string $rule,
        int $count,
        bool $isAllDay = false,
    ): array {
        $occurrences = [[$startAt->copy(), $endAt->copy()]];

        if ($rule === 'none') {
            return $occurrences;
        }

        $allowed = static::counts();
        $max = $allowed !== [] ? max($allowed) : 52;
        $total = max(2, min($count, $max));

        $durationMinutes = max(0, (int) $startAt->diffInMinutes($endAt));
        $spanDays = max(0, (int) $startAt->copy()->startOfDay()->diffInDays($endAt->copy()->startOfDay()));
        $currentStart = $startAt->copy();

        for ($index = 1; $index < $total; $index++) {
            $currentStart = match ($rule) {
                'daily' => $currentStart->copy()->addDay(),
                'weekly' => $currentStart->copy()->addWeek(),
                'biweekly' => $currentStart->copy()->addWeeks(2),
                'monthly' => $currentStart->copy()->addMonthNoOverflow(),
                default => null,
            };

            if (! $currentStart) {
                break;
            }

            if ($isAllDay) {
                $occurrenceStart = $currentStart->copy()->startOfDay();
                $occurrenceEnd = $occurrenceStart->copy()->addDays($spanDays)->endOfDay();
            } else {
                $occurrenceStart = $currentStart->copy();
                $occurrenceEnd = $occurrenceStart->copy()->addMinutes($durationMinutes);
            }

            $occurrences[] = [$occurrenceStart, $occurrenceEnd];
        }

        return $occurrences;
    }
}
