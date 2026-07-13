<?php

namespace App\Support\Portal;

use App\Models\User;
use App\Support\Portal\Dashboard\PortalDashboardCard;

class ProfileAccountOverview
{
    /**
     * @return list<PortalDashboardCard>
     */
    public static function cards(User $user): array
    {
        $verified = $user->hasVerifiedEmail();

        return [
            new PortalDashboardCard(
                label: 'Email Status',
                value: $verified ? 'Verified' : 'Pending',
                hint: $verified ? 'Notifications are enabled' : 'Verify to unlock all portal features',
                route: null,
                tone: $verified ? 'emerald' : 'amber',
                icon: 'mail',
            ),
            new PortalDashboardCard(
                label: 'Primary Role',
                value: PortalRoleLabel::for($user),
                hint: 'Current access level',
                tone: 'cyan',
                icon: 'badge',
            ),
            new PortalDashboardCard(
                label: 'Member Since',
                value: $user->created_at?->format('M Y') ?? '—',
                hint: $user->created_at?->diffForHumans() ?? 'Recently joined',
                route: null,
                tone: 'teal',
                icon: 'calendar',
            ),
        ];
    }
}
