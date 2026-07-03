<?php

namespace App\Support\Portal;

class PhoneCallResults
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return config('portal.phone_call_results', []);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_keys(self::options());
    }

    public static function label(string $value): ?string
    {
        return self::options()[$value] ?? null;
    }
}
