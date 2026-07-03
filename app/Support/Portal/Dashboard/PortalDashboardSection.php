<?php

namespace App\Support\Portal\Dashboard;

readonly class PortalDashboardSection
{
    /**
     * @param  list<PortalDashboardCard>  $cards
     */
    public function __construct(
        public string $key,
        public string $title,
        public ?string $description,
        public array $cards,
        public int $priority = 100,
    ) {}
}
