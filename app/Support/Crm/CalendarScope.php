<?php

namespace App\Support\Crm;

use App\Models\Crm\CalendarEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CalendarScope
{
    public static function userCanViewAll(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasPermission('crm.records.view-all')
            || $user->hasPermission('calendar.view-all');
    }

    public static function userCanViewTeam(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user || self::userCanViewAll($user)) {
            return false;
        }

        return TeamScope::userCanViewTeam($user);
    }

    /**
     * @param  Builder<CalendarEvent>  $query
     * @return Builder<CalendarEvent>
     */
    public static function events(Builder $query, ?User $user = null): Builder
    {
        $user ??= auth()->user();

        if (! $user) {
            return BusinessLineScope::apply($query->whereRaw('0 = 1'), $user);
        }

        if (self::userCanViewAll($user)) {
            return BusinessLineScope::apply($query, $user);
        }

        if (self::userCanViewTeam($user)) {
            return BusinessLineScope::apply(
                $query->whereIn('user_id', TeamScope::visibleUserIds($user)),
                $user,
            );
        }

        return BusinessLineScope::apply(
            $query->where('user_id', $user->id),
            $user,
        );
    }

    public static function eventIsAccessible(CalendarEvent $event, ?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        if (! in_array($event->business_line?->value ?? (string) $event->business_line, \App\Support\BusinessLineContext::valuesForQuery($user), true)) {
            return false;
        }

        if (self::userCanViewAll($user)) {
            return true;
        }

        if (! $event->user_id) {
            return false;
        }

        if (self::userCanViewTeam($user)) {
            return TeamScope::visibleUserIds($user)->contains((int) $event->user_id);
        }

        return (int) $event->user_id === (int) $user->id;
    }
}
