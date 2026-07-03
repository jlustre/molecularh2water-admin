<?php

namespace App\Contracts\Portal;

use App\Models\User;
use App\Support\Portal\Dashboard\PortalDashboardSection;

interface PortalDashboardSectionProvider
{
    public function priority(): int;

    public function section(User $user): ?PortalDashboardSection;
}
