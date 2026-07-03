<?php

namespace App\Support\Portal;

class PhoneCallReasons
{
    /**
     * @return list<array{value: string, label: string}>
     */
    public static function forContactKind(string $contactKind): array
    {
        $reasons = config('portal.phone_call_reasons', []);
        $shared = $reasons['shared'] ?? [];
        $specific = match ($contactKind) {
            'other' => $reasons['other_contact'] ?? [],
            default => $reasons[$contactKind] ?? [],
        };
        $other = $reasons['other'] ?? [['value' => 'other', 'label' => 'Other (describe in notes)']];

        return array_values(array_merge($shared, $specific, $other));
    }

    public static function label(string $value): ?string
    {
        foreach (self::all() as $reason) {
            if ($reason['value'] === $value) {
                return $reason['label'];
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (array $reason) => $reason['value'], self::all());
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function all(): array
    {
        $reasons = config('portal.phone_call_reasons', []);

        return array_values(array_merge(
            $reasons['shared'] ?? [],
            $reasons['prospect'] ?? [],
            $reasons['customer'] ?? [],
            $reasons['team'] ?? [],
            $reasons['other_contact'] ?? [],
            $reasons['other'] ?? [],
        ));
    }
}
