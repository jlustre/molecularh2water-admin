<?php

namespace App\Support\Crm;

use App\Models\Crm\Team;
use App\Models\User;
use Illuminate\Support\Collection;

class TeamScope
{
    public static function userCanViewTeam(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasPermission('crm.records.view-team')
            || $user->hasPermission('calendar.view-team');
    }

    /**
     * User IDs whose CRM records a team-scoped user may view (excludes self).
     *
     * @return Collection<int, int>
     */
    public static function memberUserIds(?User $user = null): Collection
    {
        $user ??= auth()->user();

        if (! $user) {
            return collect();
        }

        $managedTeamIds = Team::query()
            ->where('manager_id', $user->id)
            ->pluck('id');

        if ($user->hasPermission('crm.teams.manage') || $user->hasRole(['team-admin', 'admin', 'super-admin'])) {
            $managedTeamIds = $managedTeamIds
                ->merge($user->teams()->wherePivot('role', 'lead')->pluck('teams.id'))
                ->unique();
        }

        if ($managedTeamIds->isEmpty()) {
            $managedTeamIds = $user->teams()->pluck('teams.id');
        }

        if ($managedTeamIds->isEmpty()) {
            return collect();
        }

        return Team::query()
            ->whereIn('id', $managedTeamIds)
            ->with('users:id')
            ->get()
            ->flatMap(fn (Team $team) => $team->users->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $id) => $id === (int) $user->id)
            ->unique()
            ->values();
    }

    /**
     * @return Collection<int, int>
     */
    public static function visibleUserIds(?User $user = null): Collection
    {
        $user ??= auth()->user();

        if (! $user) {
            return collect();
        }

        return self::memberUserIds($user)
            ->push((int) $user->id)
            ->unique()
            ->values();
    }
}
