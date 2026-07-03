<?php

namespace App\Support;

use App\Enums\BusinessLine;
use App\Models\User;

class BusinessLineContext
{
    public const SESSION_KEY = 'crm.business_line_filter';

    /**
     * @return list<BusinessLine>
     */
    public static function linesForUser(?User $user = null): array
    {
        $user ??= auth()->user();

        if (! $user) {
            return [];
        }

        $values = $user->resolvedBusinessLineValues();

        return array_map(
            fn (string $value) => BusinessLine::from($value),
            $values,
        );
    }

    public static function showSwitcher(?User $user = null): bool
    {
        return count(self::linesForUser($user)) > 1;
    }

    /**
     * Business line choices for lead/prospect forms.
     *
     * @return list<BusinessLine>
     */
    public static function optionsForLeadForm(?User $user = null): array
    {
        return self::linesForUser($user);
    }

    public static function current(?User $user = null): string
    {
        $user ??= auth()->user();
        $available = array_map(fn (BusinessLine $line) => $line->value, self::linesForUser($user));

        if ($available === []) {
            return BusinessLine::H2s->value;
        }

        if (count($available) === 1) {
            return $available[0];
        }

        $filter = session(self::SESSION_KEY);

        if ($filter === 'all' || ! is_string($filter) || ! in_array($filter, $available, true)) {
            return $available[0];
        }

        return $filter;
    }

    public static function setCurrent(string $filter, ?User $user = null): void
    {
        $user ??= auth()->user();
        $available = array_map(fn (BusinessLine $line) => $line->value, self::linesForUser($user));

        if ($filter === 'all' || ! in_array($filter, $available, true)) {
            $filter = $available[0] ?? BusinessLine::H2s->value;
        }

        session([self::SESSION_KEY => $filter]);
    }

    /**
     * @return list<string>
     */
    public static function valuesForQuery(?User $user = null): array
    {
        $current = self::current($user);
        $available = array_map(fn (BusinessLine $line) => $line->value, self::linesForUser($user));

        if ($available === []) {
            return [];
        }

        return in_array($current, $available, true) ? [$current] : [$available[0]];
    }
}
