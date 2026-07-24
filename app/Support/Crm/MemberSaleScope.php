<?php

namespace App\Support\Crm;

use App\Models\Crm\MemberSale;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class MemberSaleScope
{
    public static function userCanManage(?User $user = null): bool
    {
        $user ??= auth()->user();

        return (bool) $user?->hasPermission('sales.manage');
    }

    public static function userCanViewTeam(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user || self::userCanManage($user)) {
            return false;
        }

        return $user->hasPermission('crm.records.view-team')
            || TeamScope::userCanViewTeam($user);
    }

    /**
     * @param  Builder<MemberSale>  $query
     * @return Builder<MemberSale>
     */
    public static function sales(Builder $query, ?User $user = null): Builder
    {
        $user ??= auth()->user();

        if (! $user) {
            return BusinessLineScope::apply($query->whereRaw('0 = 1'), $user);
        }

        if (self::userCanManage($user)) {
            return BusinessLineScope::apply($query, $user);
        }

        if (self::userCanViewTeam($user)) {
            $userIds = TeamScope::visibleUserIds($user);

            return BusinessLineScope::apply(
                $query->where(function (Builder $inner) use ($userIds) {
                    $inner->whereIn('user_id', $userIds)
                        ->orWhereIn('demo_consultant_id', $userIds);
                }),
                $user,
            );
        }

        return BusinessLineScope::apply(
            $query->where(function (Builder $inner) use ($user) {
                $inner->where('user_id', $user->id)
                    ->orWhere('demo_consultant_id', $user->id);
            }),
            $user,
        );
    }

    public static function saleIsAccessible(MemberSale $sale, ?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        return self::sales(MemberSale::query(), $user)
            ->whereKey($sale->id)
            ->exists();
    }
}
