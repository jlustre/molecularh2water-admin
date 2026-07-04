<?php

namespace App\Support\Shell;

use App\Models\User;
use App\Support\Crm\CrmRoutes;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ShellNotifications
{
    public static function canView(?User $user = null): bool
    {
        $user ??= auth()->user();

        return (bool) $user
            && $user->hasPermission('notifications.view')
            && Schema::hasTable('notifications');
    }

    public static function unreadCount(?User $user = null): int
    {
        $user ??= auth()->user();

        if (! self::canView($user)) {
            return 0;
        }

        return $user->unreadNotifications()->count();
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    public static function recent(?User $user = null, int $limit = 8): Collection
    {
        $user ??= auth()->user();

        if (! self::canView($user)) {
            return collect();
        }

        return $user->notifications()
            ->latest()
            ->limit($limit)
            ->get();
    }

    public static function message(DatabaseNotification $notification): string
    {
        $data = $notification->data;

        return (string) ($data['message'] ?? $data['title'] ?? 'Notification');
    }

    public static function url(DatabaseNotification $notification, ?User $user = null): string
    {
        $user ??= auth()->user();
        $data = $notification->data;

        if (! empty($data['task_id']) && $user?->hasPermission('tasks.view')) {
            return CrmRoutes::urlForUser($user, 'tasks.index');
        }

        if (! empty($data['appointment_id']) && $user?->hasPermission('calendar.view')) {
            return CrmRoutes::urlForUser($user, 'calendar.index');
        }

        if (! empty($data['lead_id']) && $user?->hasPermission('leads.view')) {
            return CrmRoutes::urlForUser($user, 'leads.show', ['lead' => $data['lead_id']]);
        }

        if (! empty($data['lead_id']) && $user?->hasPermission('prospects.view')) {
            return CrmRoutes::urlForUser($user, 'prospects.show', ['lead' => $data['lead_id']]);
        }

        if ($user?->canAccessAdmin()) {
            return route('admin.dashboard');
        }

        return route('dashboard');
    }

    public static function readUrl(DatabaseNotification $notification): string
    {
        return route('notifications.read', ['notification' => $notification->id]);
    }
}
