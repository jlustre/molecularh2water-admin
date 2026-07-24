<?php

namespace App\Services;

use App\Models\User;
use App\Support\Crm\CrmScope;
use App\Support\Crm\TeamScope;

class MemberOverviewAccess
{
    public function __construct(
        protected SponsorHierarchyService $sponsors,
    ) {}

    /**
     * Whether the actor may open member overview pages at all (admin / team lead / manager scopes).
     */
    public function canBrowse(?User $actor): bool
    {
        if (! $actor) {
            return false;
        }

        if ($actor->isSuperAdmin() || CrmScope::userCanViewAll($actor)) {
            return true;
        }

        if (CrmScope::userCanViewTeam($actor)) {
            return true;
        }

        return $actor->hasPermission('sponsors.view-tree');
    }

    public function canView(?User $actor, User $subject): bool
    {
        if (! $actor || ! $this->canBrowse($actor)) {
            return false;
        }

        if ((int) $actor->id === (int) $subject->id) {
            return true;
        }

        if ($actor->isSuperAdmin() || CrmScope::userCanViewAll($actor)) {
            return true;
        }

        if (CrmScope::userCanViewTeam($actor) && TeamScope::visibleUserIds($actor)->contains((int) $subject->id)) {
            return true;
        }

        if ($actor->hasPermission('sponsors.view-tree')) {
            return $this->sponsors
                ->descendants($actor, true)
                ->contains(fn (User $member) => (int) $member->id === (int) $subject->id);
        }

        return false;
    }

    public function assertCanView(?User $actor, User $subject): void
    {
        if (! $this->canView($actor, $subject)) {
            abort(403, 'You cannot view this member overview.');
        }
    }

    public function overviewUrl(User $member): string
    {
        return route('portal.team.member', $member);
    }
}
