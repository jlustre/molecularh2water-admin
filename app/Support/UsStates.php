<?php

namespace App\Support;

class UsStates
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'AL' => 'Alabama',
            'AK' => 'Alaska',
            'AZ' => 'Arizona',
            'AR' => 'Arkansas',
            'CA' => 'California',
            'CO' => 'Colorado',
            'CT' => 'Connecticut',
            'DE' => 'Delaware',
            'DC' => 'District of Columbia',
            'FL' => 'Florida',
            'GA' => 'Georgia',
            'HI' => 'Hawaii',
            'ID' => 'Idaho',
            'IL' => 'Illinois',
            'IN' => 'Indiana',
            'IA' => 'Iowa',
            'KS' => 'Kansas',
            'KY' => 'Kentucky',
            'LA' => 'Louisiana',
            'ME' => 'Maine',
            'MD' => 'Maryland',
            'MA' => 'Massachusetts',
            'MI' => 'Michigan',
            'MN' => 'Minnesota',
            'MS' => 'Mississippi',
            'MO' => 'Missouri',
            'MT' => 'Montana',
            'NE' => 'Nebraska',
            'NV' => 'Nevada',
            'NH' => 'New Hampshire',
            'NJ' => 'New Jersey',
            'NM' => 'New Mexico',
            'NY' => 'New York',
            'NC' => 'North Carolina',
            'ND' => 'North Dakota',
            'OH' => 'Ohio',
            'OK' => 'Oklahoma',
            'OR' => 'Oregon',
            'PA' => 'Pennsylvania',
            'RI' => 'Rhode Island',
            'SC' => 'South Carolina',
            'SD' => 'South Dakota',
            'TN' => 'Tennessee',
            'TX' => 'Texas',
            'UT' => 'Utah',
            'VT' => 'Vermont',
            'VA' => 'Virginia',
            'WA' => 'Washington',
            'WV' => 'West Virginia',
            'WI' => 'Wisconsin',
            'WY' => 'Wyoming',
        ];
    }

    /**
     * @return list<string>
     */
    public static function abbreviations(): array
    {
        return array_keys(self::options());
    }

    public static function abbreviation(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        $normalized = strtoupper(trim($value));

        if (array_key_exists($normalized, self::options())) {
            return $normalized;
        }

        $match = collect(self::options())
            ->search(fn (string $label) => strcasecmp($label, trim($value)) === 0);

        return $match === false ? null : $match;
    }

    public static function matches(?string $left, ?string $right): bool
    {
        $leftAbbreviation = self::abbreviation($left);
        $rightAbbreviation = self::abbreviation($right);

        if ($leftAbbreviation && $rightAbbreviation) {
            return $leftAbbreviation === $rightAbbreviation;
        }

        return filled($left) && filled($right) && strcasecmp(trim($left), trim($right)) === 0;
    }
}
