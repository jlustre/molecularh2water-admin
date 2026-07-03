<?php

namespace App\Support\Crm;

use App\Models\Crm\Activity;
use App\Models\Crm\Appointment;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\Crm\Task;
use App\Models\Crm\TimelineEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class CrmScope
{
    public static function userCanViewAll(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasPermission('crm.records.view-all');
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
     * @param  Builder<Lead>|Builder<Prospect>|Builder<Customer>|Builder<Recruit>|Relation  $query
     * @return Builder<Lead>|Builder<Prospect>|Builder<Customer>|Builder<Recruit>|Relation
     */
    public static function contacts(Builder|Relation $query, ?User $user = null): Builder|Relation
    {
        $user ??= auth()->user();

        if (! $user) {
            return BusinessLineScope::apply($query->whereRaw('0 = 1'), $user);
        }

        if (self::userCanViewAll($user)) {
            return BusinessLineScope::apply($query, $user);
        }

        if (self::userCanViewTeam($user)) {
            $userIds = TeamScope::visibleUserIds($user);

            return self::applyContactVisibilityScope(
                $query,
                $user,
                fn (Builder $branch) => self::applyContactOwnershipScope($branch, $userIds),
            );
        }

        return self::applyContactVisibilityScope(
            $query,
            $user,
            fn (Builder $branch) => $branch->where(function (Builder $scoped) use ($user) {
                $scoped
                    ->where('assigned_user_id', $user->id)
                    ->orWhereHas('owners', fn (Builder $ownerQuery) => $ownerQuery->where('users.id', $user->id));
            }),
        );
    }

    /**
     * @param  Builder<Lead>|Relation<Lead, Lead, *>  $query
     * @return Builder<Lead>|Relation<Lead, Lead, *>
     */
    public static function leads(Builder|Relation $query, ?User $user = null): Builder|Relation
    {
        return self::contacts($query, $user);
    }

    /**
     * @param  callable(Builder): void  $applyOwnership
     */
    private static function applyContactVisibilityScope(
        Builder|Relation $query,
        User $user,
        callable $applyOwnership,
    ): Builder|Relation {
        return $query->where(function (Builder $outer) use ($user, $applyOwnership) {
            $outer
                ->where(function (Builder $branch) use ($user, $applyOwnership) {
                    $applyOwnership($branch);
                    BusinessLineScope::apply($branch, $user);
                })
                ->orWhereHas('owners', fn (Builder $ownerQuery) => $ownerQuery->where('users.id', $user->id));
        });
    }

    /**
     * @param  Builder<Activity>  $query
     * @return Builder<Activity>
     */
    public static function activities(Builder $query, ?User $user = null): Builder
    {
        $user ??= auth()->user();

        if (! $user) {
            return BusinessLineScope::apply($query->whereRaw('0 = 1'), $user);
        }

        if (self::userCanViewAll($user)) {
            return BusinessLineScope::apply($query, $user);
        }

        if (self::userCanViewTeam($user)) {
            $userIds = TeamScope::visibleUserIds($user);

            return BusinessLineScope::apply(
                $query->where(function (Builder $scoped) use ($userIds) {
                    $scoped
                        ->whereIn('user_id', $userIds)
                        ->orWhereHas('contact', fn (Builder $contactQuery) => $contactQuery->whereIn('assigned_user_id', $userIds));
                }),
                $user,
            );
        }

        return BusinessLineScope::apply(
            $query->where(function (Builder $scoped) use ($user) {
                $scoped
                    ->where('user_id', $user->id)
                    ->orWhereHas('contact', fn (Builder $contactQuery) => self::contacts($contactQuery, $user));
            }),
            $user,
        );
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public static function tasks(Builder $query, ?User $user = null): Builder
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

    /**
     * @param  Builder<Appointment>  $query
     * @return Builder<Appointment>
     */
    public static function appointments(Builder $query, ?User $user = null): Builder
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

    /**
     * @param  Builder<TimelineEvent>  $query
     * @return Builder<TimelineEvent>
     */
    public static function timelineEvents(Builder $query, ?User $user = null): Builder
    {
        $user ??= auth()->user();

        if (! $user) {
            return $query->whereRaw('0 = 1');
        }

        if (self::userCanViewAll($user)) {
            return $query->whereHas('contact', fn (Builder $contactQuery) => BusinessLineScope::apply($contactQuery, $user));
        }

        return $query->whereHas('contact', fn (Builder $contactQuery) => self::contacts($contactQuery, $user));
    }

    public static function contactIsAccessible(Model $contact, ?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        $line = $contact->business_line?->value ?? (string) $contact->business_line;
        $available = \App\Support\BusinessLineContext::valuesForQuery($user);
        $isCoOwner = $contact->owners()->where('users.id', $user->id)->exists();

        if (! $isCoOwner && $line !== 'both' && ! in_array($line, $available, true)) {
            return false;
        }

        if (self::userCanViewAll($user)) {
            return true;
        }

        if (self::userCanViewTeam($user)) {
            $userIds = TeamScope::visibleUserIds($user);

            return $userIds->contains((int) $contact->assigned_user_id)
                || $contact->owners()->whereIn('users.id', $userIds)->exists();
        }

        return (int) $contact->assigned_user_id === (int) $user->id
            || $contact->owners()->where('users.id', $user->id)->exists();
    }

    public static function leadIsAccessible(Lead $lead, ?User $user = null): bool
    {
        return self::contactIsAccessible($lead, $user);
    }

    /**
     * @param  Builder  $query
     * @param  Collection<int, int>  $userIds
     */
    private static function applyContactOwnershipScope(Builder $query, Collection $userIds): void
    {
        $query->where(function (Builder $scoped) use ($userIds) {
            $scoped
                ->whereIn('assigned_user_id', $userIds)
                ->orWhereHas('owners', fn (Builder $ownerQuery) => $ownerQuery->whereIn('users.id', $userIds));
        });
    }
}
