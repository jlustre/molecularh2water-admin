<?php

namespace App\Notifications\Crm;

use App\Models\Crm\Demonstration;
use App\Models\Crm\Lead;
use App\Support\Crm\CrmRoutes;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DemoScheduledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Lead $lead,
        public Demonstration $demonstration,
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
            ->subject('Demo scheduled: '.$this->lead->fullName())
            ->line('A product demonstration has been scheduled.')
            ->line($this->demonstration->type->label().' on '.$this->demonstration->scheduled_at?->format('M j, Y g:i A'))
            ->action('View prospect', CrmRoutes::urlForUser($notifiable, 'prospects.show', ['lead' => $this->lead->id]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'lead_id' => $this->lead->id,
            'demonstration_id' => $this->demonstration->id,
            'message' => 'Demo scheduled for '.$this->lead->fullName(),
            'scheduled_at' => $this->demonstration->scheduled_at?->toIso8601String(),
        ];
    }
}
