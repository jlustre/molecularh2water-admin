<?php

namespace App\Enums\Crm;

enum StockMovementType: string
{
    case Receive = 'receive';
    case AdjustIn = 'adjust_in';
    case AdjustOut = 'adjust_out';
    case WriteOff = 'write_off';
    case Reserve = 'reserve';
    case ReleaseReserve = 'release_reserve';
    case Sale = 'sale';
    case SaleReversal = 'sale_reversal';

    public function label(): string
    {
        return match ($this) {
            self::Receive => 'Receive stock',
            self::AdjustIn => 'Adjustment (+)',
            self::AdjustOut => 'Adjustment (−)',
            self::WriteOff => 'Write-off',
            self::Reserve => 'Reserve for sale',
            self::ReleaseReserve => 'Release reservation',
            self::Sale => 'Sale fulfillment',
            self::SaleReversal => 'Sale reversal',
        };
    }

    public function isInbound(): bool
    {
        return in_array($this, [self::Receive, self::AdjustIn, self::SaleReversal], true);
    }
}
