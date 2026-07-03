<?php

namespace App\Notifications\Crm;

use App\Models\Crm\Task;
use App\Support\Crm\CrmRoutes;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Task $task,
    ) {
        $this->onQueue(config('crm.queue.notifications', 'default'));
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('CRM task reminder: '.$this->task->title)
            ->line('You have a CRM task due for follow-up.')
            ->line($this->task->title)
            ->action('View tasks', CrmRoutes::urlForUser($notifiable, 'tasks.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'due_at' => $this->task->due_at?->toIso8601String(),
            'lead_id' => $this->task->lead_id,
            'message' => 'Task reminder: '.$this->task->title,
        ];
    }
}
