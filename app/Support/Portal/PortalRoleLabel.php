<?php

namespace App\Support\Portal;

use App\Models\User;

class PortalRoleLabel
{
    public static function for(?User $user): string
    {
        if (! $user) {
            return 'Member';
        }

        return match (true) {
            $user->hasRole('super-admin') => 'Super Admin',
            $user->hasRole('team-admin') => 'Team Admin',
            $user->hasRole('admin') => 'Administrator',
            $user->hasRole('manager') => 'Manager',
            $user->hasRole('consultant') => 'Consultant',
            $user->hasRole('editor') => 'Editor',
            default => $user->roles()->orderBy('name')->value('name') ?? 'Member',
        };
    }

    public static function headlineFor(?User $user): string
    {
        if (! $user) {
            return 'Your associate workspace for resources, growth, and daily follow-ups.';
        }

        return match (true) {
            $user->hasRole(['super-admin', 'admin', 'team-admin']) => 'Organization-wide visibility across members, CRM performance, and operations.',
            $user->hasRole('manager') => 'Coach your team, monitor pipeline health, and keep field activity on track.',
            $user->hasRole('consultant') => 'Your daily command center for leads, demos, tasks, and follow-ups.',
            $user->hasRole('editor') => 'Publishing tools, media library access, and content operations at a glance.',
            $user->hasPermission('invites.manage') => 'Grow your network, share invites, and access training resources.',
            default => 'Your workspace for resources, profile settings, and sponsor tools.',
        };
    }
}
