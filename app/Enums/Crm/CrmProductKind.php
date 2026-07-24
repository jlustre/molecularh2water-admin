<?php

namespace App\Enums\Crm;

enum CrmProductKind: string
{
    case Product = 'product';
    case Gift = 'gift';

    public function label(): string
    {
        return match ($this) {
            self::Product => 'Product',
            self::Gift => 'Gift',
        };
    }
}
