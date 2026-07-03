<?php

namespace App\Support\Portal\Dashboard\Providers;

use App\Contracts\Portal\PortalDashboardSectionProvider;
use App\Models\RegistrationInvite;
use App\Models\User;
use App\Support\Portal\Dashboard\PortalDashboardCard;
use App\Support\Portal\Dashboard\PortalDashboardSection;

class NetworkSectionProvider implements PortalDashboardSectionProvider
{
    public function priority(): int
    {
        return 20;
    }

    public function section(User $user): ?PortalDashboardSection
    {
        $cards = [];

        if ($user->hasPermission('sponsors.view-tree')) {
            $teamCount = $user->sponsoredUsers()->count();

            $cards[] = new PortalDashboardCard(
                label: 'Team Members',
                value: (string) $teamCount,
                hint: 'Direct members in your sponsor tree',
                route: route('portal.team'),
                tone: 'teal',
                icon: 'users',
            );
        }

        if ($user->hasPermission('invites.manage')) {
            $activeInvites = RegistrationInvite::query()
                ->where('sponsor_id', $user->id)
                ->available()
                ->count();

            $usedInvites = RegistrationInvite::query()
                ->where('sponsor_id', $user->id)
                ->whereNotNull('consumed_at')
                ->count();

            $cards[] = new PortalDashboardCard(
                label: 'Active Invites',
                value: (string) $activeInvites,
                hint: 'Codes ready to share',
                route: route('portal.invites'),
                tone: 'cyan',
                icon: 'ticket',
            );

            $cards[] = new PortalDashboardCard(
                label: 'Registered Members',
                value: (string) $usedInvites,
                hint: 'Invites converted to accounts',
                route: route('portal.invites'),
                tone: 'emerald',
                icon: 'user-plus',
            );
        }

        if ($cards === []) {
            return null;
        }

        return new PortalDashboardSection(
            key: 'network',
            title: 'Network & Growth',
            description: 'Sponsor activity, invites, and team expansion.',
            priority: $this->priority(),
            cards: $cards,
        );
    }
}
