<?php

namespace App\Support\Crm;

use App\Models\User;

class CrmRoutes
{
    public static function isPortal(): bool
    {
        return request()->routeIs('portal.crm.*');
    }

    public static function prefixForUser(?User $user = null): string
    {
        $user ??= auth()->user();

        if ($user && $user->canAccessAdmin() && ! self::isPortal()) {
            return 'admin.crm.';
        }

        if (self::isPortal()) {
            return 'portal.crm.';
        }

        return $user && $user->canAccessAdmin() ? 'admin.crm.' : 'portal.crm.';
    }

    public static function name(string $suffix, ?User $user = null): string
    {
        return self::prefixForUser($user).$suffix;
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function url(string $suffix, array $parameters = [], ?User $user = null): string
    {
        return route(self::name($suffix, $user), $parameters);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function urlForUser(?User $user, string $suffix, array $parameters = []): string
    {
        $prefix = $user && $user->canAccessAdmin() ? 'admin.crm.' : 'portal.crm.';

        return route($prefix.$suffix, $parameters);
    }
}
