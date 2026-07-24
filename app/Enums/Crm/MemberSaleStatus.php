<?php

namespace App\Enums\Crm;

enum MemberSaleStatus: string
{
    case ApplicationStarted = 'application_started';
    case Financing = 'financing';
    case Approved = 'approved';
    case Delivered = 'delivered';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::ApplicationStarted => 'Application Started',
            self::Financing => 'Financing',
            self::Approved => 'Approved',
            self::Delivered => 'Delivered',
            self::Completed => 'Completed',
        };
    }

    public function timestampColumn(): string
    {
        return match ($this) {
            self::ApplicationStarted => 'application_started_at',
            self::Financing => 'financing_at',
            self::Approved => 'approved_at',
            self::Delivered => 'delivered_at',
            self::Completed => 'completed_at',
        };
    }
}
