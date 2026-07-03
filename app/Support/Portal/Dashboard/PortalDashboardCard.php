<?php

namespace App\Support\Portal\Dashboard;

readonly class PortalDashboardCard
{
    public function __construct(
        public string $label,
        public string $value,
        public ?string $hint = null,
        public ?string $route = null,
        public string $tone = 'teal',
        public ?string $icon = null,
    ) {}
}
