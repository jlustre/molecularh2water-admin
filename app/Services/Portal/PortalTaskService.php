<?php

namespace App\Services\Portal;

use App\Enums\Crm\TaskStatus;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\Crm\Task;
use App\Models\User;
use App\Services\Crm\CalendarEventService;
use App\Services\Crm\DashboardStatsService;
use App\Services\Crm\TaskService;
use App\Support\Crm\CrmScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PortalTaskService
{
    /**
     * @return Collection<int, Task>
     */
    public function upcomingTasks(?User $user = null, int $limit = 25): Collection
    {
        $user ??= auth()->user();

        if (! $user || ! Schema::hasTable('tasks')) {
            return collect();
        }

        return CrmScope::tasks(Task::query(), $user)
            ->with(['lead', 'user'])
            ->whereIn('status', [
                TaskStatus::Pending->value,
                TaskStatus::InProgress->value,
            ])
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array{
     *     lead_id?: int|null,
     *     title: string,
     *     description?: string|null,
     *     priority?: string,
     *     task_when?: string
     * }  $data
     */
    public function create(array $data, User $actor): Task
    {
        $dueAt = $this->resolveDueAt($data['task_when'] ?? 'none');
        $contact = $this->resolveContact($data, $actor);

        $task = app(TaskService::class)->create([
            'contact_type' => $contact?->getMorphClass(),
            'contact_id' => $contact?->id,
            'title' => trim($data['title']),
            'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
            'priority' => $data['priority'] ?? 'normal',
            'due_at' => $dueAt,
        ], $actor);

        if ($dueAt && Schema::hasTable('calendar_events')) {
            app(CalendarEventService::class)->createFromTask($task, $actor);
        }

        app(DashboardStatsService::class)->notifyChanged($actor);

        return $task->fresh(['lead', 'user']);
    }

    public function complete(Task $task, User $actor): Task
    {
        $completed = app(TaskService::class)->complete($task, $actor);

        app(DashboardStatsService::class)->notifyChanged($actor);

        return $completed;
    }

    public function findTask(int $taskId, ?User $user = null): ?Task
    {
        $user ??= auth()->user();

        if (! $user) {
            return null;
        }

        return CrmScope::tasks(Task::query(), $user)->find($taskId);
    }

    private function resolveDueAt(string $preset): ?\Illuminate\Support\Carbon
    {
        if ($preset === 'none') {
            return null;
        }

        $due = match ($preset) {
            'in_15' => now()->addMinutes(15)->ceilMinutes(15),
            'in_30' => now()->addMinutes(30)->ceilMinutes(15),
            'in_60' => now()->addHour()->ceilMinutes(15),
            'today_14' => now()->setTime(14, 0),
            'today_16' => now()->setTime(16, 0),
            'tomorrow_10' => now()->addDay()->setTime(10, 0),
            'tomorrow_14' => now()->addDay()->setTime(14, 0),
            'next_week' => now()->addWeek()->next('Monday')->setTime(10, 0),
            default => null,
        };

        if ($due && $due->isPast()) {
            $due = now()->addHour()->ceilMinutes(15);
        }

        return $due;
    }

    /**
     * @param  array{lead_id?: int|null, contact_type?: string|null, contact_id?: int|null}  $data
     */
    private function resolveContact(array $data, User $actor): Lead|Prospect|Customer|Recruit|null
    {
        if (filled($data['contact_type'] ?? null) && filled($data['contact_id'] ?? null)) {
            $class = \App\Support\Crm\CrmContactResolver::modelClassForMorph((string) $data['contact_type']);

            return CrmScope::contacts($class::query(), $actor)->find((int) $data['contact_id']);
        }

        if (blank($data['lead_id'] ?? null)) {
            return null;
        }

        foreach ([Lead::class, Prospect::class, Customer::class, Recruit::class] as $class) {
            $contact = CrmScope::contacts($class::query(), $actor)->find((int) $data['lead_id']);

            if ($contact) {
                return $contact;
            }
        }

        return null;
    }
}
