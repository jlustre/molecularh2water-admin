<?php

namespace App\Services\Crm;

use App\Enums\Crm\TaskStatus;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Crm\Recruit;
use App\Models\Crm\Task;
use App\Models\User;
use App\Support\BusinessLineResolver;
use App\Support\Crm\CrmContactResolver;
use App\Support\Crm\CrmScope;
use Illuminate\Support\Arr;

class TaskService
{
    public function __construct(
        private readonly TimelineService $timeline,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): Task
    {
        $assigneeId = CrmScope::userCanViewAll($user)
            ? (int) (Arr::get($data, 'user_id') ?: $user->id)
            : $user->id;
        $contact = $this->resolveContact($data);

        $task = Task::query()->create([
            'contact_type' => $contact?->getMorphClass(),
            'contact_id' => $contact?->id,
            'user_id' => $assigneeId,
            'business_line' => BusinessLineResolver::forRelatedContact($data, $user, $contact),
            'title' => trim((string) Arr::get($data, 'title')),
            'description' => Arr::get($data, 'description'),
            'priority' => Arr::get($data, 'priority', 'normal'),
            'status' => Arr::get($data, 'status', TaskStatus::Pending->value),
            'due_at' => Arr::get($data, 'due_at'),
            'reminder_at' => Arr::get($data, 'reminder_at'),
        ]);

        if ($contact) {
            $this->syncContactFollowUp($contact, $task->due_at);
            $this->timeline->log(
                $contact,
                'task_created',
                'Task created',
                $task->title,
                ['task_id' => $task->id],
                $user,
            );
        }

        return $task->fresh(['contact', 'user']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Task $task, array $data, User $user): Task
    {
        $contact = $this->resolveContact($data, $task);

        $task->update([
            'title' => trim((string) Arr::get($data, 'title', $task->title)),
            'description' => Arr::get($data, 'description', $task->description),
            'priority' => Arr::get($data, 'priority', $task->priority?->value ?? 'normal'),
            'status' => Arr::get($data, 'status', $task->status?->value ?? TaskStatus::Pending->value),
            'due_at' => Arr::get($data, 'due_at', $task->due_at),
            'reminder_at' => Arr::get($data, 'reminder_at', $task->reminder_at),
            'contact_type' => $contact?->getMorphClass(),
            'contact_id' => $contact?->id,
        ]);

        if ($contact) {
            $this->syncContactFollowUp($contact, $task->due_at);
        }

        return $task->fresh(['contact', 'user']);
    }

    public function complete(Task $task, User $user): Task
    {
        $task->update([
            'status' => TaskStatus::Completed,
            'completed_at' => now(),
        ]);

        if ($contact = $task->contact) {
            $this->timeline->log(
                $contact,
                'task_completed',
                'Task completed',
                $task->title,
                ['task_id' => $task->id],
                $user,
            );
        }

        return $task->fresh(['contact', 'user']);
    }

    public function delete(Task $task, User $user): void
    {
        if ($contact = $task->contact) {
            $this->timeline->log(
                $contact,
                'task_deleted',
                'Task removed',
                $task->title,
                ['task_id' => $task->id],
                $user,
            );
        }

        $task->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Lead|Prospect|Customer|Recruit|null
     */
    private function resolveContact(array $data, ?Task $existing = null): Lead|Prospect|Customer|Recruit|null
    {
        if (array_key_exists('contact_type', $data) || array_key_exists('contact_id', $data)) {
            if (blank($data['contact_type'] ?? null) || blank($data['contact_id'] ?? null)) {
                return null;
            }

            return CrmContactResolver::resolve((string) $data['contact_type'], (int) $data['contact_id']);
        }

        if (array_key_exists('lead_id', $data)) {
            if (blank($data['lead_id'])) {
                return null;
            }

            return CrmContactResolver::resolve('lead', (int) $data['lead_id']);
        }

        return $existing?->contact;
    }

    private function syncContactFollowUp(Lead|Prospect|Customer|Recruit $contact, mixed $dueAt): void
    {
        if ($dueAt) {
            $contact->update(['next_follow_up_at' => $dueAt]);
        }
    }
}
