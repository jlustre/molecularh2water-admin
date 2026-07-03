<?php

namespace App\Enums\Crm;

use Illuminate\Support\Str;

enum LeadStatus: string
{
    case New = 'new';
    case Contacting = 'contacting';
    case Active = 'active';
    case Engaged = 'engaged';
    case AttendedDemo = 'attended-demo';
    case Considering = 'considering';
    case Negotiating = 'negotiating';
    case ReadyToBuy = 'ready-to-buy';
    case Customer = 'customer';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacting => 'Contacting',
            self::Active => 'Active',
            self::Engaged => 'Engaged',
            self::AttendedDemo => 'Attended Demo',
            self::Considering => 'Considering',
            self::Negotiating => 'Negotiating',
            self::ReadyToBuy => 'Ready to Buy',
            self::Customer => 'Customer',
            self::Inactive => 'Inactive',
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

    public static function normalize(?string $value): ?self
    {
        if (blank($value)) {
            return null;
        }

        $trimmed = trim($value);

        if ($status = self::tryFrom($trimmed)) {
            return $status;
        }

        $slug = Str::slug($trimmed);

        foreach (self::cases() as $case) {
            if ($case->value === $slug) {
                return $case;
            }
        }

        $legacy = config('crm.legacy_lead_status_map', []);

        if (isset($legacy[$trimmed])) {
            return self::from($legacy[$trimmed]);
        }

        if (isset($legacy[$slug])) {
            return self::from($legacy[$slug]);
        }

        return null;
    }
}
