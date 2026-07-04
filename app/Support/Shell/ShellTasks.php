<?php

namespace App\Support\Shell;

use App\Enums\Crm\TaskStatus;
use App\Models\Crm\Task;
use App\Models\User;
use App\Support\Crm\CrmRoutes;
use App\Support\Crm\CrmScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ShellTasks
{
    public static function canView(?User $user = null): bool
    {
        $user ??= auth()->user();

        return (bool) $user
            && $user->hasPermission('tasks.view')
            && Schema::hasTable('tasks');
    }

    /**
     * @return Builder<Task>
     */
    public static function openQuery(?User $user = null): Builder
    {
        $user ??= auth()->user();

        return CrmScope::tasks(Task::query(), $user)
            ->whereIn('status', [
                TaskStatus::Pending->value,
                TaskStatus::InProgress->value,
            ]);
    }

    public static function openCount(?User $user = null): int
    {
        $user ??= auth()->user();

        if (! self::canView($user)) {
            return 0;
        }

        return self::openQuery($user)->count();
    }

    /**
     * @return Collection<int, Task>
     */
    public static function recent(?User $user = null, int $limit = 8): Collection
    {
        $user ??= auth()->user();

        if (! self::canView($user)) {
            return collect();
        }

        return self::openQuery($user)
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->limit($limit)
            ->get();
    }

    public static function indexUrl(?User $user = null): string
    {
        return CrmRoutes::url('tasks.index', user: $user);
    }
}
